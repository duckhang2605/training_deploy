<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'training_deploy');

// Project repository
set('repository', 'git@domain.com:username/repository.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', false); 

// Default branch
set('branch', 'develop');

// Setup amount version release
set('keep_releases', 1);
// Shared files/dirs between deploys
add('shared_files', [
    '.env',
]);

add('shared_dirs', [
    'storage',
    'bootstrap/cache',
]);
// Writable dirs by web server 
// Writable dirs by web server
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// Hosts

host('13.212.7.71')
	->user('vmo')
	->stage('development')
	->set('deploy_path', '~/{{application}}')
	->forwardAgent(false);    

// Tasks

task('build-assets', function () {
    run('cd {{release_path}} && yarn install');
    run('cd {{release_path}} && yarn run dev');
    run('cd {{release_path}} && composer install');
    run('cd {{release_path}} && composer update');
});

task('clear-config', [
    'artisan:cache:clear',
    'artisan:config:cache',
    'artisan:view:clear',
    'artisan:view:cache',
    'artisan:optimize:clear',
    'artisan:migrate',
]);

task('deploy', [
    // outputs the branch and IP address to the command line
    'deploy:info',
    // preps the environment for deploy, creating release and shared directories
    'deploy:prepare',
    // adds a .lock file to the file structure to prevent numerous deploys executing at once
    'deploy:lock',
    // removes outdated release directories and creates a new release directory for deploy
    'deploy:release',
    // clones the project Git repository
    'deploy:update_code',
    // loops around the list of shared directories defined in the config file
    // and generates symlinks for each
    'deploy:shared',
    // loops around the list of writable directories defined in the config file
    // and changes the owner and permissions of each file or directory
    'deploy:writable',
    // Yarn install and build assets
    'build-assets',
    // if Composer is used on the site, the Composer install command is executed
    'deploy:vendors',
    // loops around release and removes unwanted directories and files
    'deploy:clear_paths',
    // links the deployed release to the "current" symlink
    'deploy:symlink',
    // deletes the unlock file, allowing further deploys to be executed
    'deploy:unlock',
    // loops around a list of release directories and removes any which are now outdated
    'cleanup',
    // can be used by the user to assign custom tasks to execute on successful deployments
    'success'
]);

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'clear-config');

// Reload PHP-FPM
task('reload:php-fpm', function () {
    $stage = input()->hasArgument('stage') ? input()->getArgument('stage') : null;

    run('echo "Aa@123456" | sudo -S service php7.4-fpm restart -y');
    // run('echo "Aa@123456" | sudo -S service supervisor start -y');
    // run('echo "Aa@123456" | sudo -S sudo supervisorctl reload');

    switch ($stage) {
        case 'production':
            run('echo "Deploy Production Environment Successful"');
            break;

        default:
            run('echo "Deploy Staging Environment Successful"');
    }
})->desc('PHP7 FPM reloaded');

after('cleanup', 'reload:php-fpm');
