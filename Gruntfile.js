// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/* jshint node: true, browser: false */

/**
 * @copyright  2014 Andrew Nicols
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */

module.exports = function(grunt) {
    var path = require('path'),
        tasks = {},
        cwd = process.env.PWD || process.cwd();

    // shezar: Promise polyfill for legacy Node versions.
    if (typeof Promise === 'undefined') {
        global.Promise = require('promise/lib/es6-extensions');
    }

    // Windows users can't run grunt in a subdirectory, so allow them to set
    // the root by passing --root=path/to/dir.
    if (grunt.option('root')) {
        var root = grunt.option('root');
        if (grunt.file.exists(__dirname, root)) {
            cwd = path.join(__dirname, root);
            grunt.log.ok('Setting root to '+cwd);
        } else {
            grunt.fail.fatal('Setting root to '+root+' failed - path does not exist');
        }
    }

    var inAMD = path.basename(cwd) == 'amd';

    // Globbing pattern for matching all AMD JS source files.
    var amdSrc = [inAMD ? cwd + '/src/*.js' : '**/amd/src/*.js'];

    /**
     * shezar: Test if given path is for a RTL stylesheet.
     *
     * @param {String} path
     * @return {Boolean}
     */
    var isRTLStylesheet = function(path) {
         return path.match(/-rtl\.css$/);
    };

    /**
     * shezar: Is path is inside a theme with CSS that Grunt should preprocess?
     *
     * @param {String} path
     * @return {Boolean}
     */
    var preprocessTheme = function(path) {
        var dontProcess = [
            'base', 'bootstrapbase', 'standardshezarresponsive',
            'customshezarresponsive', 'kiwifruitresponsive'
        ];

        for (var i = 0; i < dontProcess.length; i++) {
            if (grunt.file.isMatch('**/theme/' + dontProcess[i] + '/**', path)) {
                return false;
            }
        }

        return true;
    };

    // shezar: Compilation of Less in core / themes
    // source files based on current dir.
    var localLess = grunt.file.isDir(cwd, 'less');
    var customThemeDir = grunt.option('themedir') || '';
    var inTheme =  false;

    // Standard theme location.
    if (path.basename(path.dirname(cwd)) === 'theme') {
        inTheme = true;
    }

    // Custom theme directory.
    if (path.basename(path.dirname(cwd)) === path.basename(customThemeDir)) {
        inTheme = true;
    }

    // Globbing pattern for Less source files.
    var lessSrc;

    if (inTheme) {
        // Single theme less only.
        lessSrc = [cwd + '/less/*.less'];
        grunt.verbose.writeln('Current directory is a theme.');
    } else if (localLess) {
        // Single component less only.
        lessSrc = [cwd + '/less/styles.less'];
        grunt.verbose.writeln('Detected local less directory.');
    } else {
        // All theme and component less files.
        lessSrc = [
            '**/less/styles.less',
            'theme/*/less/*.less'
        ];
    }

    /**
     * Generate destination paths for compiled Less files.
     *
     * @param {String} destPath The current destination
     * @param {String} srcPath The  matched src path
     * @return {String} The rewritten destination path.
     */
    var less_rename = function(destPath, srcPath) {
        var themePath = false;
        var upThreeDirs = path.basename(path.dirname(path.dirname(path.dirname(srcPath))));
        var customThemeDir = path.basename(grunt.config('themedir') || '');

        if (upThreeDirs === 'theme' || upThreeDirs === customThemeDir) {
            themePath = true;
        }

        // In themes CSS files are stored in styles directory.
        if (themePath === true) {
            var filename = path.basename(srcPath, '.less') + '.css';
            return path.join(path.dirname(path.dirname(srcPath)), 'style', filename);
        }

        // Component - styles.css file only.
        return path.join(path.dirname(path.dirname(srcPath)), 'styles.css');
    };

    var rtlSrc = 'theme/roots/style/*.css';

    if (inTheme) {
        // Single theme only. Ignore files with noprocess suffix.
        // These are intended to contain non-standard CSS placeholders
        // which cause a fatal error.
        rtlSrc = [cwd + '/style/*.css', '!' + cwd + '/style/*-noprocess.css'];
    } else if (localLess) {
        rtlSrc = [];
    } else {
        // All theme style files. Ignore *-noprocess.css files as above.
        rtlSrc = ['theme/*/style/*.css', '!theme/*/style/*-noprocess.css'];
    }

    /**
     * Rewrite destination path for RTL styles.
     *
     * @param {String} destPath
     * @param {String} srcPath
     * @return {String}
     */
    var rtl_rename = function(destPath, srcPath) {
        return srcPath.replace('.css', '-rtl.css');
    };

    /**
     * Filter expanded RTL source matches.
     *
     * @param {String} srcPath
     * @return {Boolean}
     */
    var rtl_filter = function(srcPath) {

        if (!preprocessTheme(srcPath)) {
            return false;
        }

        // Don't flip RTL files.
        return !isRTLStylesheet(srcPath);
    };

    // Imports are tried in these locations:
    //  1/ current directory
    //  2/ theme and themedir directories
    //  3/ dirroot directory
    var lessImportPaths = ['theme'];

    // Facilitate working with custom $CFG->themedir.
    if (customThemeDir !== '') {
        customThemeDir = path.resolve(cwd, customThemeDir);
        if (grunt.file.isDir(customThemeDir)) {
            grunt.log.ok("Adding custom themedir '" + customThemeDir + "' to less import search paths.");
            lessImportPaths.push(customThemeDir);
            if (!inTheme) {
                grunt.log.ok("Adding custom themedir '" + customThemeDir + "' to less sources.");
                lessSrc.push(customThemeDir + '/*/less/*.less');
                grunt.log.ok("Adding custom themedir '" + customThemeDir + "' to RTL sources.");
                rtlSrc.push(customThemeDir + '/*/style/*.css');
            }
        } else {
            grunt.fail.fatal("Custom themedir '" + customThemeDir + "' is not accessible.");
        }
    }

    // Auto prefixer source globs.
    var prefixSrc;

    if (inTheme) {
        // Current theme only.
        prefixSrc = rtlSrc;
    } else if (localLess) {
        // Single component only.
        prefixSrc = [cwd + '/styles.css'];
    } else {
        // All styles compiled from Less.
        prefixSrc = [
            rtlSrc,
            '**/styles.css'
        ];
    }

    /**
     * shezar: Filter out styles not generated from Less.
     *
     * @param {String} srcPath
     * @return {Boolean}
     */
    var prefix_filter = function(srcPath) {

        // RTL stylesheets are generated from the result of this processing.
        if (isRTLStylesheet(srcPath)) {
            return false;
        }

        if (!preprocessTheme(srcPath)) {
            return false;
        }

        // In these cases we know sources are ok.
        if (localLess || inTheme) {
            return true;
        }

        // Theme styles were included based on RTL sources so they are also ok.
        if (grunt.file.isMatch('**/theme/*/style/*.css', srcPath)) {
            return true;
        }

        // Is there a less/styles.less locally?
        return grunt.file.isFile(path.dirname(srcPath), 'less', 'styles.css');
    };

    /**
     * Function to generate the destination for the uglify task
     * (e.g. build/file.min.js). This function will be passed to
     * the rename property of files array when building dynamically:
     * http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
     *
     * @param {String} destPath the current destination
     * @param {String} srcPath the  matched src path
     * @return {String} The rewritten destination path.
     */
    var uglify_rename = function (destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        destPath = path.resolve(cwd, destPath);
        return destPath;
    };

    // Project configuration.
    grunt.initConfig({
        jshint: {
            /* TODO: remove force from the line below. Currently there is a quite a bit of cruft in shezar files which cause jshint
             * to spit out a large number of warnings.
             */
            options: {jshintrc: '.jshintrc', force: true},
            amd: { src: amdSrc }
        },
        uglify: {
            amd: {
                files: [{
                    expand: true,
                    src: amdSrc,
                    rename: uglify_rename
                }]
            }
        },
        less: {
            // shezar: Dedicated Less target.
            shezar: {
                options: {
                    compress: true,
                    paths: lessImportPaths
                },
                files: [{
                    expand: true,
                    src: lessSrc,
                    rename: less_rename
                }]
            },
            bootstrapbase: {
                files: {
                    "theme/bootstrapbase/style/moodle.css": "theme/bootstrapbase/less/moodle.less",
                    "theme/bootstrapbase/style/editor.css": "theme/bootstrapbase/less/editor.less",
                },
                options: {
                    compress: true
                }
           }
        },
        watch: {
            options: {
                nospawn: true // We need not to spawn so config can be changed dynamically.
            },
            amd: {
                files: ['**/amd/src/**/*.js'],
                tasks: ['amd']
            },
            // shezar: Add less watch target.
            less: {
                files: ['**/less/**/*.less', '!**/node_modules/**/*'],
                tasks: ['less:shezar', 'postcss:prefix', 'postcss:rtl']
            },
            bootstrapbase: {
                files: ["theme/bootstrapbase/less/**/*.less"],
                tasks: ["less:bootstrapbase"]
            },
            yui: {
                files: ['**/yui/src/**/*.js'],
                tasks: ['shifter']
            },
        },
        shifter: {
            options: {
                recursive: true,
                paths: [cwd]
            }
        },
        // shezar: PostCSS for prefixing and theme RTL.
        postcss: {
            prefix: {
                options: {
                    processors: [
                        require('autoprefixer')({ browsers: 'last 2 versions, ie >= 9' })
                    ]
                },
                files: [{
                    expand: true,
                    src: prefixSrc,
                    filter: prefix_filter
                }]
            },
            rtl: {
                options: {
                    processors: [
                        require('rtlcss')()
                    ]
                },
                files: [{
                    expand: true,
                    src: rtlSrc,
                    rename: rtl_rename,
                    filter: rtl_filter
                }]
            }
        }
    });

    /**
     * Shifter task. Is configured with a path to a specific file or a directory,
     * in the case of a specific file it will work out the right module to be built.
     *
     * Note that this task runs the invidiaul shifter jobs async (becase it spawns
     * so be careful to to call done().
     */
    tasks.shifter = function() {
        var async = require('async'),
            done = this.async(),
            options = grunt.config('shifter.options');

        // Run the shifter processes one at a time to avoid confusing output.
        async.eachSeries(options.paths, function (src, filedone) {
            var args = [];
            args.push( path.normalize(__dirname + '/node_modules/shifter/bin/shifter'));

            // Always ignore the node_modules directory.
            args.push('--excludes', 'node_modules');

            // Determine the most appropriate options to run with based upon the current location.
            if (grunt.file.isMatch('**/yui/**/*.js', src)) {
                // When passed a JS file, build our containing module (this happen with
                // watch).
                grunt.log.debug('Shifter passed a specific JS file');
                src = path.dirname(path.dirname(src));
                options.recursive = false;
            } else if (grunt.file.isMatch('**/yui/src', src)) {
                // When in a src directory --walk all modules.
                grunt.log.debug('In a src directory');
                args.push('--walk');
                options.recursive = false;
            } else if (grunt.file.isMatch('**/yui/src/*', src)) {
                // When in module, only build our module.
                grunt.log.debug('In a module directory');
                options.recursive = false;
            } else if (grunt.file.isMatch('**/yui/src/*/js', src)) {
                // When in module src, only build our module.
                grunt.log.debug('In a source directory');
                src = path.dirname(src);
                options.recursive = false;
            }

            if (grunt.option('watch')) {
                grunt.fail.fatal('The --watch option has been removed, please use `grunt watch` instead');
            }

            // Add the stderr option if appropriate
            if (grunt.option('verbose')) {
                args.push('--lint-stderr');
            }

            if (grunt.option('no-color')) {
                args.push('--color=false');
            }

            var execShifter = function() {

                grunt.log.ok("Running shifter on " + src);
                grunt.util.spawn({
                    cmd: "node",
                    args: args,
                    opts: {cwd: src, stdio: 'inherit', env: process.env}
                }, function (error, result, code) {
                    if (code) {
                        grunt.fail.fatal('Shifter failed with code: ' + code);
                    } else {
                        grunt.log.ok('Shifter build complete.');
                        filedone();
                    }
                });
            };

            // Actually run shifter.
            if (!options.recursive) {
                execShifter();
            } else {
                // Check that there are yui modules otherwise shifter ends with exit code 1.
                if (grunt.file.expand({cwd: src}, '**/yui/src/**/*.js').length > 0) {
                    args.push('--recursive');
                    execShifter();
                } else {
                    grunt.log.ok('No YUI modules to build.');
                    filedone();
                }
            }
        }, done);
    };

    tasks.startup = function() {
        // Are we in a YUI directory?
        if (path.basename(path.resolve(cwd, '../../')) == 'yui') {
            grunt.task.run('shifter');
        // Are we in an AMD directory?
        } else if (inAMD) {
            grunt.task.run('amd');
        } else {
            // Run them all!.
            grunt.task.run('css');
            grunt.task.run('js');
        }
    };

    // On watch, we dynamically modify config to build only affected files. This
    // method is slightly complicated to deal with multiple changed files at once (copied
    // from the grunt-contrib-watch readme).
    var changedFiles = Object.create(null);
    var onChange = grunt.util._.debounce(function() {
          var files = Object.keys(changedFiles);
          grunt.config('jshint.amd.src', files);
          grunt.config('uglify.amd.files', [{ expand: true, src: files, rename: uglify_rename }]);
          grunt.config('shifter.options.paths', files);
          changedFiles = Object.create(null);
    }, 200);

    grunt.event.on('watch', function(action, filepath) {
          changedFiles[filepath] = action;
          onChange();
    });

    // Register NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // shezar: Load PostCSS.
    grunt.loadNpmTasks('grunt-postcss');

    // Register JS tasks.
    grunt.registerTask('shifter', 'Run Shifter against the current directory', tasks.shifter);
    grunt.registerTask('amd', ['jshint', 'uglify']);
    grunt.registerTask('js', ['amd', 'shifter']);

    // Register shezar tasks.
    grunt.registerTask('css', ['less:shezar', 'postcss:prefix', 'postcss:rtl']);

    // Register the startup task.
    grunt.registerTask('startup', 'Run the correct tasks for the current directory', tasks.startup);

    // Register the default task.
    grunt.registerTask('default', ['startup']);
};
