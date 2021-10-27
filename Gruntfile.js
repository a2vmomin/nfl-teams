module.exports = function(grunt) {

    'use strict';

    // Project configuration
    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        addtextdomain: {
            options: {
                textdomain: 'nfl-teams',
            },
            update_all_domains: {
                options: {
                    updateDomains: true
                },
                src: ['*.php', '**/*.php', '!\.git/**/*', '!bin/**/*', '!node_modules/**/*', '!tests/**/*']
            }
        },

        wp_readme_to_markdown: {
            your_target: {
                files: {
                    'README.md': 'readme.txt'
                }
            },
        },

        makepot: {
            target: {
                options: {
                    domainPath: '/languages',
                    exclude: ['\.git/*', 'bin/*', 'node_modules/*', 'tests/*'],
                    mainFile: 'nfl-teams.php',
                    potFilename: 'nfl-teams.pot',
                    potHeaders: {
                        poedit: true,
                        'x-poedit-keywordslist': true
                    },
                    type: 'wp-plugin',
                    updateTimestamp: true
                }
            }
        },

        sass: {
            dist: {
                options: {
                    sourcemap: false,
                    compress: false,
                    yuicompress: false,
                    style: 'expanded',
                },
                files: {
                    'css/base.css': 'sass/base.scss',
                    'css/main.css': 'sass/main.scss'
                }
            },
        },
        watch: {
            css: {
                files: '**/*.scss',
                tasks: ['sass']
            }
        }
    });

    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default', ['i18n', 'readme']);
    grunt.registerTask('i18n', ['addtextdomain', 'makepot']);
    grunt.registerTask('readme', ['wp_readme_to_markdown']);

    grunt.registerTask('default', ['watch']);

    grunt.util.linefeed = '\n';

};