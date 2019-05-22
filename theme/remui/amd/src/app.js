/*! AdminLTE app.js
 * ================
 * Main JS application file for AdminLTE v2. This file
 * should be included in all pages. It controls some layout
 * options and implements exclusive AdminLTE plugins.
 *
 * @Author  Almsaeed Studio
 * @Support <http://www.almsaeedstudio.com>
 * @Email   <abdullah@almsaeedstudio.com>
 * @version 2.3.8
 * @license MIT <http://opensource.org/licenses/MIT>
 */

/* jshint ignore:start */
define(['jquery', 'theme_remui/resize_sensor', 'theme_remui/slimscroll', 'core/log'], function($, ResizeSensor, slimscroll, log) {

    "use strict"; // jshint ;_;

    log.debug('Admin LTE AMD initialised');

    /* AdminLTE
     *
     * @type Object
     * @description $.AdminLTE is the main object for the template's app.
     *              It's used for implementing functions and options related
     *              to the template. Keeping everything wrapped in an object
     *              prevents conflict with other plugins and is a better
     *              way to organize our code.
     */
    $.AdminLTE = {};

    /* --------------------
     * - AdminLTE Options -
     * --------------------
     * Modify these options to suit your implementation
     */
    $.AdminLTE.options = {
        //Add slimscroll to navbar menus
        //This requires you to load the slimscroll plugin
        //in every page before app.js
        navbarMenuSlimscroll: true,
        navbarMenuSlimscrollWidth: "4px", //The width of the scroll bar
        navbarMenuHeight: "175px", //The height of the inner menu
        //General animation speed for JS animated elements such as box collapse/expand and
        //sidebar treeview slide up/down. This options accepts an integer as milliseconds,
        //'fast', 'normal', or 'slow'
        animationSpeed: 200,
        //Sidebar push menu toggle button selector
        sidebarToggleSelector: "[data-toggle='offcanvas']",
        //Activate sidebar push menu
        sidebarPushMenu: true,
        //Activate sidebar slimscroll if the fixed layout is set (requires SlimScroll Plugin)
        sidebarSlimScroll: true,
        //Enable sidebar expand on hover effect for sidebar mini
        //This option is forced to true if both the fixed layout and sidebar mini
        //are used together
        sidebarExpandOnHover: false,
        //BoxRefresh Plugin
        enableBoxRefresh: false,
        //Bootstrap.js tooltip
        enableBSToppltip: false,
        BSTooltipSelector: "[data-toggle='tooltip']",
        
        //Control Sidebar Options
        enableControlSidebar: true,
        controlSidebarOptions: {
            //Which button should trigger the open/close event
            toggleBtnSelector: "[data-toggle='control-sidebar']",
            //The sidebar selector
            selector: ".control-sidebar",
            //Enable slide over content
            slide: true,

            // update slide var based on theme settings
            slide: $(".rightsidebar-toggle").attr('data-slide')
        },
        //Box Widget Plugin. Enable this plugin
        //to allow boxes to be collapsed and/or removed
        enableBoxWidget: true,
        //Box Widget plugin options
        boxWidgetOptions: {
            boxWidgetIcons: {
                //Collapse icon
                collapse: 'fa-minus',
                //Open icon
                open: 'fa-plus',
                //Remove icon
                remove: 'fa-times'
            },
            boxWidgetSelectors: {
                //Remove button selector
                remove: '[data-widget="remove"]',
                //Collapse button selector
                collapse: '[data-widget="collapse"]'
            }
        },
        
        //Define the set of colors to use globally around the website
        colors: {
            lightBlue: "#3c8dbc",
            red: "#f56954",
            green: "#00a65a",
            aqua: "#00c0ef",
            yellow: "#f39c12",
            blue: "#0073b7",
            navy: "#001F3F",
            teal: "#39CCCC",
            olive: "#3D9970",
            lime: "#01FF70",
            orange: "#FF851B",
            fuchsia: "#F012BE",
            purple: "#8E24AA",
            maroon: "#D81B60",
            black: "#222222",
            gray: "#d2d6de"
        },
        //The standard screen sizes that bootstrap uses.
        //If you change these in the variables.less file, change
        //them here too.
        screenSizes: {
            xs: 480,
            sm: 768,
            md: 992,
            lg: 1200
        }
    };

    /* ------------------
     * - Implementation -
     * ------------------
     * The next block of code implements AdminLTE's
     * functions and plugins as specified by the
     * options above.
     */
    $(function() {
        "use strict";

        //Fix for IE page transitions
        $("body").removeClass("hold-transition");

        //Extend options if external options exist
        if (typeof AdminLTEOptions !== "undefined") {
            $.extend(true,
                $.AdminLTE.options,
                AdminLTEOptions);
        }

        //Easy access to options
        var o = $.AdminLTE.options;

        //Set up the object
        _init();

        //Activate the layout maker
        $.AdminLTE.layout.activate();

        //Enable sidebar tree view controls
        $.AdminLTE.tree('.sidebar');

        //Enable control sidebar
        if (o.enableControlSidebar) {
            $.AdminLTE.controlSidebar.activate();
        }

        //Fix content height on control sidebar height change
        $.AdminLTE._tree('.controlsidebarpost');

        //Add slimscroll to navbar dropdown
        if (o.navbarMenuSlimscroll && typeof $.fn.slimscroll != 'undefined') {
            $(".navbar .menu").slimscroll({
                height: o.navbarMenuHeight,
                alwaysVisible: false,
                size: o.navbarMenuSlimscrollWidth
            }).css("width", "100%");
        }

        //Activate sidebar push menu
        if (o.sidebarPushMenu) {
            $.AdminLTE.pushMenu.activate(o.sidebarToggleSelector);
        }

        //Activate Bootstrap tooltip
        if (o.enableBSToppltip) {
            $('body').tooltip({
                selector: o.BSTooltipSelector
            });
        }

        //Activate box widget
        if (o.enableBoxWidget) {
            $.AdminLTE.boxWidget.activate();
        }

        /*
         * INITIALIZE BUTTON TOGGLE
         * ------------------------
         */
        $('.btn-group[data-toggle="btn-toggle"]').each(function() {
            var group = $(this);
            $(this).find(".btn").on('click', function(e) {
                group.find(".btn.active").removeClass("active");
                $(this).addClass("active");
                e.preventDefault();
            });

        });
    });

    /* ----------------------------------
     * - Initialize the AdminLTE Object -
     * ----------------------------------
     * All AdminLTE functions are implemented below.
     */
    function _init() {
        //'use strict';
        /* Layout
         * ======
         * Fixes the layout height in case min-height fails.
         *
         * @type Object
         * @usage $.AdminLTE.layout.activate()
         *        $.AdminLTE.layout.fix()
         *        $.AdminLTE.layout.fixSidebar()
         */
        $.AdminLTE.layout = {
            activate: function() {
                var _this = this;
                //_this.fix();
                _this.fixSidebar();

                $(window, ".wrapper").resize(function() {
                    _this.fix();
                    _this.fixSidebar();
                });
            },
            fix: function() {
                //Get window height and the wrapper height
                var neg = $('.main-header').outerHeight() + $('.main-footer').outerHeight();
                var window_height = $(window).height();
                var sidebar_height = $(".sidebar").height();

                //Set the min-height of the content and sidebar based on the
                //the height of the document.
                if ($("body").hasClass("fixed")) {
                    $(".content-wrapper, .right-side").css('min-height', window_height - $('.main-footer').outerHeight());
                } else {

                    var postSetWidth;
                    if (window_height >= sidebar_height) {
                        $(".content-wrapper, .right-side").css('min-height', window_height - neg);
                        postSetWidth = window_height - neg;
                    } else {
                        $(".content-wrapper, .right-side").css('min-height', sidebar_height);
                        postSetWidth = sidebar_height;
                    }

                    //Fix for the control sidebar height
                    var controlSidebar = $($.AdminLTE.options.controlSidebarOptions.selector);
                    if (typeof controlSidebar !== "undefined") {
                        if (controlSidebar.height() > postSetWidth)
                            $(".content-wrapper, .right-side").css('min-height', controlSidebar.height());
                    }
                }
            },
            fixSidebar: function() {
                //Make sure the body tag has the .fixed class
                if (!$("body").hasClass("fixed")) {
                    if (typeof $.fn.slimScroll != 'undefined') {
                        $(".sidebar").slimScroll({
                            destroy: true
                        }).height("auto");

                        $(".controlsidebarpost").slimScroll({
                            destroy: true
                        }).height("auto");
                    }
                    return;
                } else if (typeof $.fn.slimScroll == 'undefined' && window.console) {
                    window.console.error("Error: the fixed layout requires the slimscroll plugin!");
                }
                //Enable slimscroll for fixed layout
                if ($.AdminLTE.options.sidebarSlimScroll) {
                    if (typeof $.fn.slimScroll != 'undefined') {
                        //Destroy if it exists
                        $(".sidebar").slimScroll({
                            destroy: true
                        }).height("auto");

                        $(".controlsidebarpost").slimScroll({
                            destroy: true
                        }).height("auto");

                        //Add slimscroll
                        $(".sidebar").slimscroll({
                            height: ($(window).height() - $(".main-header").height()) + "px",
                            color: "rgba(0,0,0,0.2)",
                            size: "3px"
                        });

                        $(".controlsidebarpost").slimscroll({
                            height: ($(window).height() - $(".main-header").height()) + "px",
                            color: "rgba(0,0,0,0.2)",
                            size: "3px"
                        });
                    }
                }
            }
        };

        /* PushMenu()
         * ==========
         * Adds the push menu functionality to the sidebar.
         *
         * @type Function
         * @usage: $.AdminLTE.pushMenu("[data-toggle='offcanvas']")
         */
        $.AdminLTE.pushMenu = {
            activate: function(toggleBtn) {
                //Get the screen sizes
                var screenSizes = $.AdminLTE.options.screenSizes;

                //Enable sidebar toggle
                $(toggleBtn).on('click', function(e) {
                    e.preventDefault();

                    //Enable sidebar push menu
                    if ($(window).width() > (screenSizes.sm - 1)) {
                        if ($("body").hasClass('sidebar-collapse')) {
                            $("body").removeClass('sidebar-collapse').trigger('expanded.pushMenu');
                        } else {
                            $("body").addClass('sidebar-collapse').trigger('collapsed.pushMenu');
                        }
                    }
                    //Handle sidebar push menu for small screens
                    else {
                        if ($("body").hasClass('sidebar-open')) {
                            $("body").removeClass('sidebar-open').removeClass('sidebar-collapse').trigger('collapsed.pushMenu');
                        } else {
                            $("body").addClass('sidebar-open').trigger('expanded.pushMenu');
                        }
                    }
                });

                $(".content-wrapper").click(function() {
                    //Enable hide menu when clicking on the content-wrapper on small screens
                    if ($(window).width() <= (screenSizes.sm - 1) && $("body").hasClass("sidebar-open")) {
                        $("body").removeClass('sidebar-open');
                    }
                });

                //Enable expand on hover for sidebar mini
                if ($.AdminLTE.options.sidebarExpandOnHover || ($('body').hasClass('fixed') && $('body').hasClass('sidebar-mini'))) {
                    this.expandOnHover();
                }
            },
            expandOnHover: function() {
                var _this = this;
                var screenWidth = $.AdminLTE.options.screenSizes.sm - 1;
                //Expand sidebar on hover
                $('.main-sidebar').hover(function() {
                    if ($('body').hasClass('sidebar-mini') && $("body").hasClass('sidebar-collapse') && $(window).width() > screenWidth) {
                        _this.expand();
                    }
                }, function() {
                    if ($('body').hasClass('sidebar-mini') && $('body').hasClass('sidebar-expanded-on-hover') && $(window).width() > screenWidth) {
                        _this.collapse();
                    }
                });
            },
            expand: function() {
                $("body").removeClass('sidebar-collapse').addClass('sidebar-expanded-on-hover');
            },
            collapse: function() {
                if ($('body').hasClass('sidebar-expanded-on-hover')) {
                    $('body').removeClass('sidebar-expanded-on-hover').addClass('sidebar-collapse');
                }
            }
        };

        /* Tree()
         * ======
         * Converts the sidebar into a multilevel
         * tree view menu.
         *
         * @type Function
         * @Usage: $.AdminLTE.tree('.sidebar')
         */
        $.AdminLTE.tree = function(menu) {
            var _this = this;
            // use resize sensor to detect height change of sidebar
            // fixing content wrapper height based on sidebar nad vice versa
            new ResizeSensor($(menu), function() {
                _this.layout.fix();
                _this.layout.fixSidebar();
            });
        };

        /* ControlSidebar
         * ==============
         * Adds functionality to the right sidebar
         *
         * @type Object
         * @usage $.AdminLTE.controlSidebar.activate(options)
         */
        $.AdminLTE.controlSidebar = {
            //instantiate the object
            activate: function() {
                //Get the object
                var _this = this;
                //Update options
                var o = $.AdminLTE.options.controlSidebarOptions;
                //Get the sidebar
                var sidebar = $(o.selector);
                //The toggle button
                var btn = $(o.toggleBtnSelector);
                //Get the screen sizes
                var screenSizes = $.AdminLTE.options.screenSizes;

                /* on small screens close the sidebar on click outside */
                $(".content-wrapper").click(function() {
                    // //Enable hide menu when clicking on the content-wrapper on small screens
                    if ($(window).width() <= (screenSizes.sm - 1)) {
                        _this.close(sidebar, o.slide);
                    }
                });

                //Listen to the click event
                btn.on('click', function(e) {
                    e.preventDefault();
                    //If the sidebar is not open
                    if (!sidebar.hasClass('control-sidebar-open') && !$('body').hasClass('control-sidebar-open')) {
                        //Open the sidebar
                        _this.open(sidebar, o.slide);
                    } else {
                        _this.close(sidebar, o.slide);
                    }
                });

                //If the body has a boxed layout, fix the sidebar bg position
                var bg = $(".control-sidebar-bg");
                _this._fix(bg);

                //If the body has a fixed layout, make the control sidebar fixed
                if ($('body').hasClass('fixed')) {
                    _this._fixForFixed(sidebar);
                } else {
                    //If the content height is less than the sidebar's height, force max height
                    if ($('.content-wrapper, .right-side').height() < sidebar.height()) {
                        _this._fixForContent(sidebar);
                    }
                }
            },
            //Open the control sidebar
            open: function(sidebar, slide) {

                $(".rightsidebar-toggle").find('.fa').removeClass('fa-arrow-left');
                $(".rightsidebar-toggle").find('.fa').addClass('fa-arrow-right');

                //Slide over content
                if (slide) {
                    sidebar.addClass('control-sidebar-open');
                } else {
                    //Push the content by adding the open class to the body instead
                    //of the sidebar itself
                    $('body').addClass('control-sidebar-open');
                }
            },
            //Close the control sidebar
            close: function(sidebar, slide) {

                $(".rightsidebar-toggle").find('.fa').addClass('fa-arrow-left');
                $(".rightsidebar-toggle").find('.fa').removeClass('fa-arrow-right');

                if (slide) {
                    sidebar.removeClass('control-sidebar-open');
                } else {
                    $('body').removeClass('control-sidebar-open');
                    $('.control-sidebar-open').attr('display', 'none');
                }
            },
            _fix: function(sidebar) {
                var _this = this;
                if ($("body").hasClass('layout-boxed')) {
                    sidebar.css('position', 'absolute');
                    sidebar.height($(".wrapper").height());
                    $(window).resize(function() {
                        _this._fix(sidebar);
                    });
                } else {
                    sidebar.css({
                        'position': 'fixed',
                        'height': 'auto'
                    });
                }
            },
            _fixForFixed: function(sidebar) {
                sidebar.css({
                    'position': 'fixed',
                    'max-height': '100%',
                    'padding-bottom': '50px'
                });
            },
            _fixForContent: function(sidebar) {
                $(".content-wrapper, .right-side").css('min-height', sidebar.height());
            },
            _fixForHeightChange: function(sidebar) {

                var sidebar_height = $(".sidebar").height();
                var csidebar_height = $(sidebar).height();

                // check which sidebar height is more and adjust content wrapper height accordingly
                var min_height = (sidebar_height > csidebar_height) ? sidebar_height : csidebar_height;
                $(".content-wrapper, .right-side").css('min-height', min_height);
            }
        };

        $.AdminLTE._tree = function(menu) {
            var _this = this;
            //Get the sidebar
            var sidebar = $($.AdminLTE.options.controlSidebarOptions.selector);

            new ResizeSensor($(sidebar), function() {

                var msidebar_height = $(sidebar).height();

                // If the body has a fixed layout, make the control sidebar fixed
                if ($('body').hasClass('fixed')) {
                    _this.controlSidebar._fixForFixed(sidebar);
                } else {
                    _this.controlSidebar._fixForHeightChange(sidebar);
                }

                // If the body has a boxed layout, fix the sidebar bg position
                var bg = $(".control-sidebar-bg");
                _this.controlSidebar._fix(bg);

            });
        };

        /* BoxWidget
         * =========
         * BoxWidget is a plugin to handle collapsing and
         * removing boxes from the screen.
         *
         * @type Object
         * @usage $.AdminLTE.boxWidget.activate()
         *        Set all your options in the main $.AdminLTE.options object
         */
        $.AdminLTE.boxWidget = {
            selectors: $.AdminLTE.options.boxWidgetOptions.boxWidgetSelectors,
            icons: $.AdminLTE.options.boxWidgetOptions.boxWidgetIcons,
            animationSpeed: $.AdminLTE.options.animationSpeed,
            activate: function(_box) {
                var _this = this;
                if (!_box) {
                    _box = document; // activate all boxes per default
                }
                //Listen for collapse event triggers
                $(_box).on('click', _this.selectors.collapse, function(e) {
                    e.preventDefault();
                    _this.collapse($(this));
                });

                //Listen for remove event triggers
                $(_box).on('click', _this.selectors.remove, function(e) {
                    e.preventDefault();
                    _this.remove($(this));
                });
            },
            collapse: function(element) {
                var _this = this;
                //Find the box parent
                var box = element.parents(".box").first();
                var boxname = $(box).data('name');
                //Find the body and the footer
                var box_content = box.find("> .box-body, > .box-footer, > form  >.box-body, > form > .box-footer");
                if (!box.hasClass("collapsed-box")) {
                    //Convert minus into plus
                    element.children(":first")
                        .removeClass(_this.icons.collapse)
                        .addClass(_this.icons.open);
                    //Hide the content
                    box_content.slideUp(_this.animationSpeed, function() {
                        box.addClass("collapsed-box");
                    });
                    
                    // save value in user prefs moodle
                    M.util.set_user_preference(boxname, 1);
                } else {
                    //Convert plus into minus
                    element.children(":first")
                        .removeClass(_this.icons.open)
                        .addClass(_this.icons.collapse);
                    //Show the content
                    box_content.slideDown(_this.animationSpeed, function() {
                        box.removeClass("collapsed-box");
                    });
                    
                    // save value in user prefs moodle
                    M.util.set_user_preference(boxname, 0);
                }
            },
            remove: function(element) {
                //Find the box parent
                var box = element.parents(".box").first();
                box.slideUp(this.animationSpeed);
            }
        };
    }

    /* ------------------
     * - Custom Plugins -
     * ------------------
     * All custom plugins are defined below.
     */

    /*
     * BOX REFRESH BUTTON
     * ------------------
     * This is a custom plugin to use with the component BOX. It allows you to add
     * a refresh button to the box. It converts the box's state to a loading state.
     *
     * @type plugin
     * @usage $("#box-widget").boxRefresh( options );
     */
    (function() {

        $.fn.boxRefresh = function(options) {

            // Render options
            var settings = $.extend({
                //Refresh button selector
                trigger: ".refresh-btn",
                //File source to be loaded (e.g: ajax/src.php)
                source: "",
                //Callbacks
                onLoadStart: function(box) {
                    return box;
                }, //Right after the button has been clicked
                onLoadDone: function(box) {
                        return box;
                    } //When the source has been loaded

            }, options);

            //The overlay
            var overlay = $('<div class="overlay"><div class="fa fa-refresh fa-spin"></div></div>');

            return this.each(function() {
                //if a source is specified
                if (settings.source === "") {
                    if (window.console) {
                        window.console.log("Please specify a source first - boxRefresh()");
                    }
                    return;
                }
                //the box
                var box = $(this);
                //the button
                var rBtn = box.find(settings.trigger).first();

                //On trigger click
                rBtn.on('click', function(e) {
                    e.preventDefault();
                    //Add loading overlay
                    start(box);

                    //Perform ajax call
                    box.find(".box-body").load(settings.source, function() {
                        done(box);
                    });
                });
            });

            function start(box) {
                //Add overlay and loading img
                box.append(overlay);

                settings.onLoadStart.call(box);
            }

            function done(box) {
                //Remove overlay and loading img
                box.find(overlay).remove();

                settings.onLoadDone.call(box);
            }

        };

    });

    /*
     * EXPLICIT BOX ACTIVATION
     * -----------------------
     * This is a custom plugin to use with the component BOX. It allows you to activate
     * a box inserted in the DOM after the app.js was loaded.
     *
     * @type plugin
     * @usage $("#box-widget").activateBox();
     */
    (function() {

        $.fn.activateBox = function() {
            $.AdminLTE.boxWidget.activate(this);
        };

    });
    
    return $.AdminLTE;
});
/* jshint ignore:end */