document.addEventListener('DOMContentLoaded', function(){
 
    /* Auto adjust navigation menu in header according to window width
     */
    if(document.getElementsByClassName('navbar-custom-menu')[0]) {
        var widthfix        = document.getElementsByClassName('navbar-custom-menu')[0].offsetWidth;
        var navbar_width    = document.getElementsByClassName('navbar')[0].offsetWidth - 105 - 115;
        var available_width = navbar_width - widthfix;

        // get menu
        var robj = document.getElementsByClassName('horizontal-main-menu')[0];
        
        // make menu responsive
        alignMenu(robj);
    }
    // handle menu on resize
    if(document.getElementsByClassName('navbar-custom-menu')[0]) {
        window.addEventListener('resize', function(event) {
            widthfix        = document.getElementsByClassName('navbar-custom-menu')[0].offsetWidth;
            navbar_width    = document.getElementsByClassName('navbar')[0].offsetWidth - 105 - 115;
            available_width = navbar_width - widthfix;

            // add li items from more li to main menu
            robj.innerHTML = robj.innerHTML + document.getElementsByClassName("hideshow-ul")[0].innerHTML;
            
            // revert dropdown-submenu class dropdown on parent li elements
            var items = robj.children;
            for (var i = 0; i < items.length; i++) {
                if (items[i].classList.contains('dropdown-submenu')) {
                    items[i].classList.toggle('dropdown');
                    items[i].classList.toggle('dropdown-submenu');
                }
            }
            // empty the more li item
            document.getElementsByClassName("hideshow-ul")[0].innerHTML = '';
            alignMenu(robj);
        })
    };

    function alignMenu(obj) {
        var current_menuwidth = 0;
        var max_menuwidth = available_width;
        var extra_lis = Array();
    
        var items = obj.children;

        for (var i = 0; i < items.length; i++) {
            if(items[i].className != 'hideshow dropdown pull-right') {
                current_menuwidth += items[i].offsetWidth;
                if (max_menuwidth < current_menuwidth) {
                    extra_lis.push(items[i]);
                }
            }
        }

        // add extra elements in more ul
        document.getElementsByClassName("hideshow-ul")[0].innerHTML = '';
        for (var i = 0; i < extra_lis.length; i++) {
            
            if (extra_lis[i].classList.contains('dropdown')) {
                extra_lis[i].classList.toggle('dropdown');
                extra_lis[i].classList.toggle('dropdown-submenu');
            }

            document.getElementsByClassName("hideshow-ul")[0].appendChild(extra_lis[i]);
        }

        // show 3 dot more menu if menu items are hidden
        if (max_menuwidth < current_menuwidth) {
            document.getElementsByClassName("hideshow")[0].style.visibility = "visible";
        } else {
            document.getElementsByClassName("hideshow")[0].style.visibility = "hidden";
        }
    }

    // move quiz timer from sidebar to content-wrapper for mobile devices
    if(document.getElementById('#quiz-timer')) {
        var quiztimer = document.querySelector('#quiz-timer');
        var quiztimer_new = document.querySelector('#quiztimer');
        var breadcrumb = document.querySelector(".content-breadcrumb");

        if (quiztimer && quiztimer_new) {
            breadcrumb.parentNode.insertBefore(quiztimer, breadcrumb.nextSibling);
        }
    }

    // fix content layout on page load
    // get window height and the wrapper height
    if (document.querySelectorAll('.content-wrapper').length > 0) {

        var header_height = document.getElementsByClassName('main-header')[0].offsetHeight;

        var footer_height = document.getElementsByClassName('main-footer')[0].offsetHeight;

        var neg = header_height + footer_height;
        var window_height = window.innerHeight;
        var sidebar_height = document.getElementsByClassName('sidebar')[0].offsetHeight;

        //Set the min-height of the content and sidebar based on the
        //the height of the document.
        if (document.body.classList.contains('fixed')) {
            var ch = window_height - footer_height;
            document.getElementsByClassName('content-wrapper')[0].style.minHeight = (ch - 10) + "px";
        } else {
            var postSetWidth;
            if (window_height >= sidebar_height) {
                document.getElementsByClassName('content-wrapper')[0].style.minHeight = (window_height - neg - 10) + "px";
                postSetWidth = window_height - neg;
            } else {
                document.getElementsByClassName('content-wrapper')[0].style.minHeight = (sidebar_height - 10) + "px";
                postSetWidth = sidebar_height;
            }

            // //Fix for the control sidebar height
            var controlSidebar = document.getElementsByClassName('control-sidebar')[0];

            if (typeof controlSidebar !== "undefined") {
                if (controlSidebar.offsetHeight > postSetWidth) {
                    document.getElementsByClassName('content-wrapper')[0].style.minHeight = controlSidebar.offsetHeight + "px";
                }
            }
        }
    }

    // Adding place holder on the Login page
    if (document.getElementById("username")) {
        document.getElementById("username").placeholder = "Username";
        document.getElementById("password").placeholder = "Password";
    }

    // check if it is a message page or not.
    if (document.querySelector('.message .messagearea')) {
        var outdiv = document.querySelector('.message');
        var parent = document.querySelector('.message .messagearea');
        if (parent.innerHTML === '') {
            parent.innerHTML = "<h3>Please choose a contact from the list.</h3>";
        }
        parent.innerHTML = '<div class="messagearea-wrap">' + parent.innerHTML + '</div>';
        // rapping up everthing in the 'messagearea-wrap' adding it to the .messagearea div.
        outdiv.appendChild(parent);
    }
}, false);