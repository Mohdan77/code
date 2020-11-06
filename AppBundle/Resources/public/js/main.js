// == main script ==
var MQ = {
    mobil: 500
};
var windowWidth = window.innerWidth;
var isMobile = Boolean(windowWidth < MQ.mobil);

var catKoleri = document.location.href.indexOf('koleri');
(function () {
    //setup style for COLOR preview in category KOLERI
    if (catKoleri != "-1") {
        $('.product_section .product-image_blk .image .color, .default_product .col_image .color').addClass('category-koleri');
    }
}());

//function plugin init
function initVendor() {
    if (!isMobile) {
        $('.categories_blk').masonry({
            itemSelector: '.item',
            columnWidth: '.item-sizer',
            percentPosition: true
        });
    }

    $(".fancybox").fancybox({
        openEffect: 'none',
        closeEffect: 'none'
    });


    $(".js-product-img-lightbox").fancybox({
        openEffect: 'none',
        closeEffect: 'none',

        beforeShow: function () {
            var alt = this.element.find('img').attr('alt');

            if (alt) {
                this.inner.find('img').attr('alt', alt);
                this.title = alt;
            } else {
                alt = this.element.next().find("img").attr('alt');
                this.title = alt;
            }
        },

        afterLoad: function (current) {
            var _element = current.element.context;
            var COLOR_ATTR_NAME = 'data-color';

            if (_element.hasAttribute(COLOR_ATTR_NAME)) {
                var colorElement = document.createElement('div');

                colorElement.className = 'ps-lightbox-color';
                colorElement.style.backgroundColor = _element.getAttribute(COLOR_ATTR_NAME)

                $(current.inner).append(colorElement)
            }
            //change size colorElement for big img and cat Koleri
            var color = $('.fancybox-wrap .ps-lightbox-color');
            var specificProduct = document.location.href.indexOf("fuga-elastichnaja-ilmax-mastic-plus-belaja-rb-20-kg")
            if ((catKoleri == "-1") && ($('.product_section .product-image_blk .image img').width() > 200) && ($(window).width() > '1023')) {
                color.width(color.width() * 2);
                color.height(color.height() * 2);
            } else if (specificProduct != "-1") {
                color.width(150);
                color.height(150);
            } else if (catKoleri != "-1") {
                color.width(170);
                color.height(170);
            }
        }
    });

    $('.order-list').dropList({
        num: 2,
        link: '._ico-dots'
    });

    var autoComplete = function (container) {
        var path = container.data('url');
        var parent = container.parent();
        var searchPage = '/search/?search=';

        //Шаблоны
        var categoryTmp = function (title, url) {
                var a = document.createElement('a');
                a.innerHTML = '<span class="_ico-arr-right"></span>' + title;
                a.target = '_self';
                a.href = url;
                return a;
            },
            productTmp = function (title, url) {
                var a = document.createElement('a');
                a.innerHTML = title;
                a.target = '_self';
                a.href = url;
                return a;
            },
            totalTmp = function (title, count, url) {
                var a = document.createElement('a');
                a.innerHTML = title + ' ' + count;
                a.target = '_self';
                a.className = 'js-search-total';
                return a;
            };

        container.autocomplete({
            source: path,
            minLength: 2,
            appendTo: parent,
            position: {my: "right top", at: "right bottom"},
            select: function (e, ui) {
                var location = '';

                if (ui.item.type === 'total_products') {
                    location = searchPage + e.target.value;
                } else {
                    location = ui.item.value;
                }
                window.location = location;
            }
        }).autocomplete("instance")._renderItem = function (ul, item) {

            if (item.type === 'category') {
                // Добовление категорий в список
                return $("<li class='_category'>").append(function () {
                    return categoryTmp(item.label, item.value);
                }).appendTo(ul);
            } else if (item.type === 'product') {
                // Добовление продуктов в список
                return $("<li class='_product'>").append(function () {
                    return productTmp(item.label, item.value);
                }).appendTo(ul);
            } else if (item.type === 'total_products' && item.total > item.max) {
                return $("<li class='_total_products'>").append(function () {
                    return totalTmp('Показать еще:', item.total - item.max)
                }).appendTo(ul);
            } else {
                return $("<li>").appendTo(ul);
            }
        }
    };

    $('.autocomplete').each(function (index) {
        autoComplete($(this));
    });

    var mobileSearch = function (trigger, search) {
        var fix = true;

        var close = function () {
            search.removeAttr('data-status');
            fix = true;
        };

        trigger.on('click, touchstart', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (fix) {
                search.attr('data-status', 'visible');
                fix = !fix;
            } else {
                close();
            }
        });

        $(document).on('click, touchstart', function (e) {
            close();
        });

        search.on('click, touchstart', function (e) {
            e.stopPropagation();
        });


        search.find('.ui-autocomplete').on('click, touchstart', function (e) {
            e.stopPropagation();
        });

        search.find('input').on('click', function (e) {
            e.stopPropagation();
        })
    };
    mobileSearch($('#jsSearchWTrg'), $('#jsSearchW'));
    mobileSearch($('#jsSearchW2Trg'), $('#jsSearchW2'));

    (function () {
        var widgetInput = $('.day-widget').find('input');

        var NYdateDisabled = widgetInput.data('weekend')
            ? widgetInput.data('weekend').split(",")
            : [];

        widgetInput.datepicker({
            // Todo-note: Отключение дат
            beforeShowDay: function (date) {
                var string = jQuery.datepicker.formatDate('dd.mm.yy', date);
                return [NYdateDisabled.indexOf(string) === -1]
            }
        });
    }());


    jQuery(function ($) {
        $.datepicker.regional['ru'] = {
            closeText: 'Закрыть',
            prevText: '',
            nextText: '',
            currentText: 'Сегодня',
            monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
            monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
                'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
            dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
            dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
            dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
            weekHeader: 'Нед',
            dateFormat: 'dd.mm.yy',
            firstDay: 1,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: '',
            minDate: (0),
            showOtherMonths: true,
            selectOtherMonths: true
        };
        $.datepicker.setDefaults($.datepicker.regional['ru']);
    });
}

initVendor();

(function () {
    var categoriesBlk = document.querySelector('.categories_blk');
    if (categoriesBlk) {
        if (isMobile) {
            var items = [].slice.call(categoriesBlk.querySelectorAll('.item'));
            categoriesBlk.setAttribute('data-mobile-item', '');

            items.forEach(function (item, i, arr) {
                var subcat = item.querySelector('.vertical_nav');
                var link = item.querySelector('a');

                if (subcat) {
                    var trigger = document.createElement('span');

                    trigger.className = 'trigger';
                    item.appendChild(trigger);

                    var closeOther = function () {
                        arr.forEach(function (item) {
                            var subcat = item.querySelector('.vertical_nav');

                            $(subcat).slideUp();
                            item.removeAttribute('data-open');
                        })
                    };

                    var toggle = function (e) {
                        e.stopPropagation();
                        e.preventDefault();

                        if (!item.hasAttribute('data-open')) {
                            closeOther();
                            $(subcat).slideDown();
                            item.setAttribute('data-open', '');
                        } else {
                            $(subcat).slideUp();
                            item.removeAttribute('data-open');
                        }
                    };

                    link.addEventListener('click', toggle);
                    trigger.addEventListener('click', toggle)
                }
            })
        }
    }
}());

(function () {
    var searchWidgets = Array.from(document.querySelectorAll('.js-search-widget'));

    if (searchWidgets.length) {
        searchWidgets.forEach(function (widget) {
            var input = widget.querySelector('input');
            var submit = widget.querySelector('button');

            submit.addEventListener('click', function (e) {
                e.stopPropagation();

                if (!input.value) {
                    e.preventDefault();
                }
            });
        });
    }
}());

//ready
$(document).ready(function () {
    //general app module
    var app = (function () {
        var overlay = $('.function_overlay');
        // click box function
        var clickBox = function (item, data) {
            item.on('click', function () {
                if (data) {
                    window.location.href = $(this).data('link');
                } else {
                    window.location.href = $(this).parent().find('a').attr('href');
                }
            });
        };
        // change class function
        var changeClass = function (item, $class, func) {
            item.on('click', function () {
                var self = $(this);
                if (self.hasClass($class)) {
                    return false
                } else {
                    item.removeClass($class);
                    self.addClass($class);
                    if (func) {
                        func();
                    }
                }
            });
        };
        var $toggle = function (btn, elem) {
            if (elem) {
                btn.on('click', function () {
                    elem.toggleClass('active');
                    return false
                });
                $(document).on('click', function (e) {
                    if (!$(e.target).is(elem)) {
                        elem.removeClass('active');
                    }
                });

                elem.on('click', function (e) {
                    e.stopPropagation();
                })
            }
        };

        var tooltipCustom = function (elem, title, placement) {

            var body = $('body');

            elem.tooltip({
                trigger: 'manual',
                title: title ? title : 'Подсказка',
                placement: placement ? placement : 'bottom'
            });

            elem.tooltip('show');

            setTimeout(function () {
                elem.tooltip('destroy');
            }, 3000);

            setTimeout(function () {
                body.on('click', function () {
                    elem.tooltip('destroy');
                    body.off('click')
                });
            }, 1000)

        };

        var popup = function (popup, btn, callback) {
            var overlay = $('.popup_overlay'),
                close = popup.find('._ico-close');

            function openPopup() {
                overlay.show();
                popup.fadeIn(300);

                if (callback && typeof callback == 'function') {
                    callback();
                }
            }

            function closePopup() {
                overlay.hide();
                popup.fadeOut(300);
            }

            if (btn) {
                btn.on('click', function (e) {
                    e.preventDefault();
                    openPopup();
                });
            } else {
                openPopup();
            }

            overlay.on('click', function () {
                closePopup();
            });

            close.on('click', function () {
                closePopup();
            });
        };

        // Public API
        return {
            clickBox: clickBox,
            changeClass: changeClass,
            $toggle: $toggle,
            popup: popup,
            tooltipCustom: tooltipCustom,
            overlay: overlay
        };
    })();

    // temporary popup
    (function () {
        if (document.querySelector('.info__popup')) {
            var storage = localStorage;
            var now = Math.floor(Date.now() / 1000); // current unix time in seconds
            var period = 60 * 60 * 9; // popup reset period in seconds
            var storageName = 'temp_popup';
            var state = +storage.getItem(storageName);
            if (!state || (now > state + period)) {
                setTimeout(function () {
                    app.popup($('.info__popup'), false, function () {
                        storage.setItem(storageName, now)
                    });
                }, 500);
            }
        }
    })();

    (function () {
        $('._ico-question').on('click', function () {
            $(this).tooltip({
                trigger: 'manual',
                title: $(this).data('toolip'),
                placement: 'bottom',
                html: true
            });

            $(this).tooltip('toggle');

            $('.tooltip').hasClass('in') ? null : $('.tooltip').addClass('in');

            return false;
        });
    })();
    $(document).on('click', function (e) {
        var tooltip = $('.tooltip');
        var icoQuestion = $('._ico-question');
        if (!tooltip.is(e.target) && !icoQuestion.is(e.target)
            && icoQuestion.has(e.target).length === 0
            && tooltip.has(e.target).length === 0
            && (tooltip.length > 0)
            && (tooltip.css('opacity') == '1')) {
            tooltip.removeClass('in');
        }
    });


    (function () {
        app.popup($('.mobil-phones_popup'), $('.mobil-phones_popup-btn'));
        app.popup($('.cart-clear__popup'), $('.cart-clear__trg'));
    }());

    /*if ((windowWidth < 650) && window.disableFilterButton) {
        $('.btn-filter-catalog_blk').remove();
        $('.wrapper-filter-catalog_blk').slideToggle();
    }*/

    // counter module
    function counterNum(container) {
        //private variables
        var less = container.find('._ico-minus'),
            more = container.find('._ico-plus'),
            text = container.find('input');
        more.on('click', function () {
            var text = $(this).siblings('input');
            if (parseInt(text.val()) < 19999) {
                text.val(function (index, value) {
                    return value = parseInt(value) + 1
                });
            }
            text.trigger('change')
        });
        less.on('click', function () {
            var text = $(this).siblings('input');
            if (parseInt(text.val()) >= 1) {
                text.val(function (index, value) {
                    return value = parseInt(value) - 1
                });
            }
            text.trigger('change')
        });

        pf_setOnlyNumber(text);

        text.keypress(function (event) {
            if (event.keyCode == 13) return false;
        });

        // Limit the maximum number
        text.keyup(function (event) {
            if (parseInt($(this).val()) > 19999) {
                $(this).val(19999);
            }
        });
    }

    // init counter
    if ($("div").is('._counter')) {
        counterNum($('._counter'));
    }

    (function () {
        var counters = [].slice.call(document.querySelectorAll('.js-cart-quantity'));

        if (counters.length) {
            counters.forEach(function (el) {
                var item = $(el).closest('.js-cart-item')[0],
                    itemPrice = item.querySelector('.js-cart-item-price'),
                    itemTotal = item.querySelector('.js-cart-item-total');

                $(el).on('change', function (e) {
                    e.stopPropagation();
                    var input = el.querySelector('input'),
                        value = input.value,
                        url = input.getAttribute('data-update'),
                        data = {},
                        cartWeight = document.getElementById('js-cart-weight'),
                        cartDiscount = document.getElementById('js-cart-discount'),
                        cartTotal = document.getElementById('js-cart-total'),
                        cartWarning = document.getElementById('js-cart-warning'),
                        total = ((parseInt(value) * (parseFloat(itemPrice.innerHTML.replace(",", ".")) * 1000)) / 1000);

                    itemTotal.innerHTML = String((total.toFixed(2)).replace('.', ','));

                    data[input.name] = value;

                    $.post(url, data, function (data) {

                        cartTotal.innerHTML = data.cart_total + ' руб.';

                        if (parseFloat(data.cart_total_discount.replace(",", ".")) * 1000) {
                            cartDiscount.innerHTML =
                                '<div class="title">Ваша скидка:</div>\n' +
                                '<div class="content">' + data.cart_total_discount + ' руб.\n' +
                                '</div>'
                        } else {
                            cartDiscount.innerHTML = ''
                        }
                        if (cartWeight && data.total_weight) {
                            cartWeight.innerHTML = data.total_weight + ' кг.';

                            if (parseFloat(data.total_weight.replace(" ", "").replace(",", ".")) > 2100) {
                                cartWarning.innerHTML =
                                    '<p style="margin-bottom: 12px">' +
                                    '<span style="color: red">*</span> ' +
                                    'При превышении массы 2100 кг, стоимость\n' +
                                    'доставки<br>будет рассчитываться менеджером индивидуально.' +
                                    '</p> '
                            } else {
                                cartWarning.innerHTML = ''
                            }
                        }
                    });

                })
            })
        }
    })();


    // categories
    var categories = (function () {
        var item = $('.categories_nav > li'),
            item2 = $('.second-level > li:not(.selsub-active)'),
            sub = $(item).find('.subnav'),
            item3 = $('.second-level > li'),
            subsub = $(item3).find('.subsubnav');
        fix = true;
        item2.hover(function () {
            var self2 = $(this);
            item2.removeClass('hover');
            self2.not('.sel').addClass('hover');
            app.overlay.show();
        });
        item.hover(function () {
            var self = $(this);
            item.removeClass('hover');
            self.not('.sel').addClass('hover');
            app.overlay.show();
        });
        app.overlay.hover(function () {
            item.removeClass('hover');
            item2.removeClass('hover');
            app.overlay.hide()
        });
        subsub.each(function () {
            var trigger1 = $(this).parent().find('._ico-arr-right');
            trigger1.show().on('click', function () {
                var parent1 = $(this).parent();
                if (parent1.hasClass('selsub-active')) {
                    if (fix) {
                        parent1.addClass('close');
                        fix = !fix;
                    } else {
                        parent1.removeClass('close');
                        fix = !fix;
                    }
                }
            });
        });
        sub.each(function () {
            var trigger = $(this).parent().find('._ico-arr-right');
            trigger.show().on('click', function () {
                var parent = $(this).parent();
                if (parent.hasClass('sel')) {
                    if (fix) {
                        parent.addClass('close');
                        fix = !fix;
                    } else {
                        parent.removeClass('close');
                        fix = !fix;
                    }
                }
            });
        });
    })();

    // slider
    var setting = {
        speed: 300,
        animation: 'easeInSine',
        timer: 7000,
        summary: {
            hide: -300,
            show: 65,
            anim: 'easeOutSine',
            duration: 400
        }
    };

    var slider = (function (setting) {
        var container = $('.move-slider_blk'),
            nav = container.find('.navigation'),
            navigation = {
                left: container.find('._ico-arr-left'),
                right: container.find('._ico-arr-right')
            },
            slide = container.find('.slide'),
            slideCount = slide.length,
            dots = container.find('.dots_nav'),
            current = 0,
            timer;

        //dots
        for (var i = 0; i < slideCount; i++) {
            dots.append('<li></li>')
        }

        dots.css({'margin-left': -(dots.width() / 2)});

        var dot = dots.find('li');
        dot.eq(0).addClass('sel');


        //function fade
        function fade(current) {
            var currentSummary = slide.eq(current - 1).find('.summary'),
                nextSummary = slide.eq(current).find('.summary');

            currentSummary
                .animate(
                    {
                        top: setting.summary.hide,
                        opacity: 0
                    },
                    setting.summary.duration,
                    setting.summary.anim);

            dot
                .removeClass('sel')
                .eq(current)
                .addClass('sel');

            slide
                .delay(100)
                .css({'z-index': 10})
                .animate({opacity: 0},
                    setting.speed,
                    setting.animation)
                .eq(current)
                .css({'z-index': 20})
                .animate({opacity: 1},
                    setting.speed,
                    setting.animation,
                    function () {
                        nextSummary
                            .delay(100)
                            .animate(
                                {
                                    top: setting.summary.show,
                                    opacity: 1
                                },
                                setting.speed + 100,
                                setting.animation)
                    });
        }

        // function move right
        function moveRight() {
            if (current == slideCount - 1) {
                current = 0
            } else {
                current++
            }
            fade(current)
        }

        // function move left
        function moveLeft() {
            if (current == 0) {
                current = slideCount - 1
            } else {
                current--
            }
            fade(current)
        }

        // click right arrow
        navigation.right.on('click', function () {
            if (!slide.is(':animated')) {
                moveRight()
            }

        });

        // click left arrow
        navigation.left.on('click', function () {
            if (!slide.is(':animated')) {
                moveLeft()
            }
        });

        //click dot
        dot.on('click', function () {
            if (!slide.is(':animated')) {
                var index = $(this).index();
                current = index;
                fade(index)
            }
        });

        // slide click box
        slide.on('click', function () {
            window.location.href = $(this).find('a').attr('href');
            return false
        });

        // hover slider
        container.hover(function () {
            nav.addClass('hover');
            clearInterval(timer)
        }, function () {
            timer = setInterval(moveRight, setting.timer);
            nav.removeClass('hover');
        });

        if (setting.timer) {
            timer = setInterval(moveRight,
                setting.timer)
        }
    })(setting);

    // switch catalog view
    var swicthCatView = (function (container) {
        var item = container.find('li span'),
            catalog = $('._catalog');
        app.changeClass(item, 'sel', function () {
            catalog.toggleClass('mini_catalog');
        })
    })($('.view-switch_nav'));

    // tabs
    var tabs = (function (container) {
        var navItem = container.find('.tabs_nav li'),
            tab = container.find('.tab');


        app.changeClass(navItem, 'sel');
        navItem.on('click', function () {
            var index = $(this).index();
            tab.removeClass('active').eq(index).addClass('active')
        });
    })($('.tabs_blk'));

    //order tables
    var myOrders = (function (container) {
        var item = container.find('tbody tr');
        app.clickBox(item, true);
    })($('.my-order_blk'));

    (function () {
        app.clickBox($('.article-cat_item'))
    })();


    //sort
    (function (container) {
        var btn = $('.price_sort'),
            product = container.find('.col_12'),
            fix = true;

        // translate into numbers
        function priceNumber(self) {
            var text = self.find('.basePrice:first').text();
            return (parseFloat(text.replace(' р./шт.', '').replace(',', '.'))) * 100
        }

        // sorting function
        function sorting() {
            var list = product.get();
            product.remove();
            list.sort(function (a, b) {
                var compA = priceNumber($(a));
                var compB = priceNumber($(b));


                if (fix) {
                    return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
                } else {
                    return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
                }
            });
            $.each(list, function (idx, itm) {
                container.append(itm);
            });
            if (fix) {
                btn.removeClass('desc').addClass('asc');
                fix = !fix;
            } else {
                btn.removeClass('asc').addClass('desc');
                fix = !fix;
            }
            counterNum($('._counter'));
            productCartSend($('.default_product').find('.send-cart'), 40);
        }

        ////default action
        //sorting();

        //click button action
        btn.on('click', function () {
            sorting();
            return false
        })
    })($('._catalog > ._row'));

    // filter
    (function (filter) {
        var select = filter.find('select'),
            product = $('._catalog > [class$="_row"]', $('.products_blk')).find('.col_12'),
            productsBlk = $('.products_blk');

        //empty service array
        var elems = [],
            param = [];

        //confirm template
        var confirmTemplate = '<div class="confirm_blk" style="margin-top:48px;width:180px">' +
            '<span class="_ico-warning"></span>' +
            'Товары не найдены' +
            '</div>';

        // remove (replace static)
        (function () {
            for (var i = 0; i < product.length; i++) {
                $(product[i]).attr('data-id', 'id' + i);
            }
        })();

        //create param grid
        (function () {
            for (var i = 0; i < product.length; i++) {
                var data = $(product[i]).data('parameters').trim().split(' '),
                    id = $(product[i]).data('id');
                data.unshift(id);
                elems.push(data)
            }
        })();

        // change function
        select.on('change', function () {
            var self = $(this),
                index = self.closest('.control-row').index(),
                result = [];

            // hide product
            product.hide();

            // param array
            param[index] = self.val();

            // filter active select
            var activeSel = select.filter(function (index) {
                var selIndex = $(this).prop('selectedIndex');
                return selIndex > 0;
            });

            // selection of appropriate
            for (var i = 0; i < elems.length; i++) {
                var intersecResult = intersec(elems[i], param);
                if (intersecResult.length > activeSel.length - 1) {
                    result.push(elems[i][0]);
                }
            }

            //painting
            if (result.length > 0) {
                productsBlk.find('.confirm_blk').remove();
                for (var k = 0; k < result.length; k++) {
                    product.filter('[data-id=' + result[k] + ']').show()
                }
            } else {
                productsBlk.find('.confirm_blk').remove();
                productsBlk.append(confirmTemplate)
            }
        })
    })($('.filter_blk'));

    //mobile swipe menu
    (function () {
        app.$toggle($('#swipe-menu_btn'), $('.swipe-menu_mobile'));
        app.$toggle($('#head-close-icon'), $('.swipe-menu_mobile'))
        app.$toggle($('#head-close-icon'), $('#swipe-menu_btn'))
    })();

    //mobile profile  menu
    (function () {
        app.$toggle($('#profile-menu_btn'), $('#profile-menu_mobile'))
    })();

    // checkout section func
    var checkOutSection = (function (section) {

        var delivery = $('#checkout_delivery'),
            unloading = $('#checkout_unloading'),
            unloadingLimit = unloading.data('limit'),
            deliverySelect = delivery.find('.change select'),
            unloadingElems = unloading.find('input, select'),
            unloadingSelect = unloadingElems.filter('select'),
            deliveryPanel = delivery.find('.panel'),
            unloadingPanel = unloading.find('.panel'),
            deliverySummary = delivery.find('.summary').find('div'),
            zoneSelect = $('#delivery'),
            unloadingUrl = unloading.data('url'),
            dateRow = delivery.find('.date-row'),
            floor = 50,
            checkout = {},
            totalBlk = $('.checkout-report_blk '),
            keepFormDataUrl = section.data('url');


        // set checkout default property
        checkout.price = section.data('total-price');

        // only number
        pf_setOnlyNumber(unloading.find('#floor'));

        // set checkout method total
        checkout.total = function () {

            //check the uploading price
            if (typeof this.uploadingPrice !== 'number') {
                this.uploadingPrice = 0
            }

            // set total price
            this.totalPrice = this.price + this.deliveryPrice + this.uploadingPrice;

            // painting
            section.find('.deliveryText').html(this.deliveryPrice.formatMoney(2) + ' руб.');
            section.find('.uploadingText').html(this.uploadingPrice.formatMoney(0) + ' руб.');
            section.find('.totalPriceText').html(this.totalPrice.formatMoney(2) + ' руб.');
        };

        // switch function
        function switchFunc(scope, condition) {
            if (scope.val() !== condition && scope.val() !== '') {
                return true;
            } else {
                return false;
            }
        }

        // set delivery price function
        function deliveryTotalPrice() {
            var factor = deliverySelect.find('option:selected').data('factor'),
                zone = zoneSelect.find('option:selected').data('price');
            if (factor > 0) {
                if (zone > 0) {
                    checkout.deliveryPrice = zone * factor;
                } else {
                    checkout.deliveryPrice = 0;
                }
            }
        }

        // delivery panel open function
        function deliveryFuncOpen() {
            if (!deliveryPanel.is(':visible')) {
                // show panel
                deliveryPanel.show();
                // show unloading select
                unloading.show();
            }

            // set delivery price
            deliveryTotalPrice();

            // painting
            checkout.total();
        }

        // delivery panel close function
        function deloveryFuncClose() {
            if (deliveryPanel.is(':visible')) {
                // hide panel
                deliveryPanel.hide();

                // hide unloading select
                unloading.hide();

                // unloading close
                unloading.trigger('unloadingClose');

                // date close
                dateRow.trigger('dataRowClose');

                // reset delivery price
                checkout.deliveryPrice = 0;

                // reset address
                deliveryPanel.find('#address').val('');

                // reset zone select
                zoneSelect.find('option').eq(0).prop('selected', 'selected');

                // hide texts
                $('#infoDeliveryZone').removeClass('zoneDefined');
                $('.delivery_price').css('display', 'none');

                // painting
                checkout.total();
            }
        }

        // function delivery summary
        function deliverySummaryFunc(index) {
            deliverySummary.hide();
            if (deliverySummary.eq(index)) {
                deliverySummary.eq(index).show();
            } else {
                return false
            }
        }

        // unloading close event
        unloading.on('unloadingClose', function () {

            // unloading panel hide
            unloadingPanel.hide();


            $('#floor').prop('required', false);
            floorFlag = false;

            // unloading element reset options
            unloadingElems.each(function () {
                var self = $(this),
                    name = self.prop('name');

                switch (name) {
                    case 'unloading':
                        self.val('');
                        self[0].selectedIndex = 0;
                        break;
                    case 'floor':
                        self.val('');
                        break;
                    case 'elevator':
                        self.val('0');
                        self.prop('checked', false);
                        break;
                    case 'extraFloor':
                        self.prop('checked', false);
                        break
                }
            });

            // reset uploading price
            checkout.uploadingPrice = 0;

            // painting
            checkout.total();
        });

        //data row close event
        dateRow.on('dataRowClose', function () {
            var self = $(this);
            self.find('input').val('');
            self.find('select option').eq(0).prop('selected', 'selected');
            self.hide()
        });


        // deliver select onChange
        deliverySelect.on('change', function () {
            var self = $(this),
                index = self.prop('selectedIndex');

            // action
            if (switchFunc(self, 'order.purchase.pickup')) {
                deliveryFuncOpen(self)
            } else {
                deloveryFuncClose()
            }

            if (self.val() == 'order.purchase.delivery') {
                dateRow.show();
            } else {
                // date close
                dateRow.trigger('dataRowClose');
            }

            switch (index) {
                case 2:
                    deliverySummaryFunc(0);
                    totalBlk.show();
                    break;
                default:
                    deliverySummary.hide();
                    totalBlk.show();
                    break;
            }
        });

        deliverySelect.trigger('change');

        var floorFlag = false;

        //unloading select onChange
        unloadingElems.on('change', function () {
            var elem = $(this),
                floorElem = $('#floor', section),
                param = {'delivery': zoneSelect.find('option:selected').val()};


            // unloading elements action
            unloadingElems.each(function () {
                var self = $(this);

                // hide / show unloading panel
                if (self.prop('id') == 'unloading') {
                    if (switchFunc(self, 'order.unloading.none')) {
                        unloadingPanel.show();
                        floorElem.prop('required', true);
                    } else {
                        unloading.trigger('unloadingClose');
                    }
                }

                // set post request parameters
                param[self.prop('name')] = self.val();
                if (self.prop('type') == 'checkbox') {
                    if (self.prop('checked')) {
                        param[self.prop('name')] = 1;
                    } else {
                        param[self.prop('name')] = 0;
                    }
                }
            });

            function postRequest(callback) {
                $.post(
                    unloadingUrl,
                    param,
                    callback
                );
            }

            floorFlag = true;
            postRequest(onPostSuccess);

            // check floor number
            if (elem.prop('name') == 'floor') {
                if (elem.val() > floor) {
                    floorElem.val(floor);
                    app.tooltipCustom(floorElem, 'Слишком много этажей');
                }
            }


            // post request success function
            function onPostSuccess(data) {
                // set delivery price
                deliveryTotalPrice();

                unloadingLimit = data.limit;

                // set uploading price
                if (floorFlag) {
                    if (checkout.uploadingPrice = data.price) {
                        $('.delivery_price').css('display', 'block');
                    }else {$('.delivery_price').css('display', 'none');
                    }
                }
                // painting
                checkout.total();
            }
        });

        //zone select onChange
        zoneSelect.on('change', function () {
            // set uploading price
            deliveryTotalPrice();

            // trigger change on any of unloadingElems
            unloadingElems.first().trigger('change');

            // painting
            checkout.total();
        });

        // order unloading price
      /* unloadingSelect.change(function () {
            $(this).val() == 'order.unloading.room'
                ? $('.delivery_price').css('display', 'block')
                : $('.delivery_price').css('display', 'none')
        });*/

        // show unloading panel if order data is kept
        if (unloadingSelect.val() == 'order.unloading.room') {
            unloadingSelect.trigger('change');
        }

        // show zone text if order data is kept
        if ($('#delivery').prop('selectedIndex') !== 0) {
            $('#infoDeliveryZone').html($('#delivery option:selected').text()).addClass('zoneDefined');
        }

        // keep order form data
        section.find(':input').change(function () {
            var params = section.find('form').serialize();

            $.post(
                keepFormDataUrl,
                params
            );
        });

    })($('.checkout_section'));

    // send product of cart
    function productCartSend(btn, height, msg, setting) {

        var cartBlk = $('.cart_blk');

        // handler click event function
        function btnHandler() {

            var self = $(this),
                parent = self.closest('form'),
                input = parent.find('input'),
                num = input.val(),
                param = {id: parent.data('id'), quantity: num ? num : 1},
                $set;

            // set general setting
            if (setting) {
                $set = setting;
            } else {
                $set = {
                    url: parent.attr('action'),
                    msg: msg ? msg : parent.find('.msg')
                };
            }

            // message close function
            function closeMsg(msg) {
                setTimeout(function () {
                    msg.animate({'opacity': 0, 'bottom': -height}, 100);
                }, 700);
            }

            // default action
            self.off('click');
            $set.msg.css({'bottom': 0, 'opacity': 1});

            // post request
            $.post(
                $set.url,
                param,
                onPostSuccess
            );

            // response function
            function onPostSuccess(data) {
                $('a.cart').find('.num').html(data.cart_quantity);
                cartBlk.find('.summ').html(data.cart_total.formatMoney(2) + ' р.');
                input.val('1');
                closeMsg($set.msg);
                self.on('click', btnHandler);
            }

            return false
        }

        // init event
        btn.on('click', btnHandler)
    }

    // init productCartSend function default product
    (function () {
        productCartSend($('.default_product').find('.send-cart'), 40);
        productCartSend($('.related_product').find('.send-cart'), 40);
    })();


    // init productCartSend product page
    (function (section) {
        var msg = section.find('.msg'),
            btn = section.find('.send-cart');
        productCartSend(btn, 40, msg);
    })($('.product-image_blk'));

    // Рейтинг
    (function () {
        var starRating = [].slice.call(document.querySelectorAll(".js-rating-control"));

        if (starRating[0]) {
            starRating.forEach(function (rating) {
                var stars = [].slice.call(rating.querySelectorAll('._star')),
                    hidden = rating.querySelector('.rating-hidden');

                stars.forEach(function (star) {
                    star.addEventListener('click', function (e) {
                        e.preventDefault();

                        stars.forEach(function (el) {
                            el.removeAttribute('data-checked')
                        });

                        this.setAttribute('data-checked', '');
                        hidden.value = this.getAttribute('data-rating');
                    })
                })
            })
        }
    })();

    // Форма отзывов
    (function () {
        var reviewForm = document.querySelector('#jsReviewForm');
        if (reviewForm) {

            var headline = reviewForm.querySelector('.blk_headline'),
                form = reviewForm.querySelector('.blk_body'),
                fix = true;

            headline.addEventListener('click', function (e) {
                e.preventDefault();

                if (fix) {
                    this.setAttribute('data-selected', '');
                    $(form).slideDown('fast');
                    fix = !fix;
                } else {
                    this.removeAttribute('data-selected');
                    $(form).slideUp('fast');
                    fix = !fix;
                }
            })
        }
    })();

    // Превью рейтинга
    (function () {
        var ratingPreview = [].slice.call(document.querySelectorAll('.c-rating-preview'))
        if (ratingPreview[0]) {
            ratingPreview.forEach(function (rating) {
                var value = parseFloat(rating.getAttribute('data-value')),
                    active = rating.querySelector('._active'),
                    current = (100 / 5) * value;

                active.style.width = current.toFixed(2) + '%';

            })
        }
    })();

    // Пагинация отзывов
    (function () {
        var section = document.querySelector('.product_section');
        if (section) {
            var search = window.location.search,
                hash = window.location.hash,
                tabs = [].slice.call(section.querySelectorAll('.tab')),
                navItem = [].slice.call(section.querySelectorAll('.tabs_nav li')),
                offset = section.querySelector('.tabs_nav').offsetTop,
                rating = section.querySelector('.c-rating-preview');

            var changeRaviewTab = function () {
                $("html, body").animate({scrollTop: offset});

                tabs.forEach(function (tab, i) {
                    $(tab).removeClass('active');
                    $(navItem).eq(i).removeClass('sel');
                    if (tab.getAttribute('data-type') === 'review') {
                        $(tab).addClass('active');
                        $(navItem).eq(i).addClass('sel')
                    }
                });
            };

            if (search.indexOf('page') > 0 || hash === "#jsReviewForm") {
                changeRaviewTab();
            }
            if (rating) {
                rating.addEventListener('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    changeRaviewTab();
                    window.location.hash = "#jsReviewForm";
                })
            }
        }
    })();

    // Маска телефона
    (function () {
        var phoneInputs = Array.from(document.querySelectorAll('input[data-type=phone]'));

        if (phoneInputs.length) {
            phoneInputs.map(function (input) {
                new Inputmask('+375 (99) 999-99-99').mask(input);
            })
        }
    }());

    // Mobile Slider
    (function () {
        var owlMobSlider = $("#js-mobile-slider").owlCarousel({
            loop: true,
            responsiveClass: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            smartSpeed: 700,
            margin: 12,
            responsive: {
                0: {
                    items: 1,
                    slideBy: 1
                },
                600: {
                    items: 2
                }
            }
        });

        owlMobSlider.on("dragged.owl.carousel", function (event) {
            owlMobSlider.trigger('stop.owl.autoplay');
            owlMobSlider.trigger('play.owl.autoplay');
        });
    }());


    // advantages mobile

    (function () {

        $(".our_advantages_mob").owlCarousel({
            loop: true,
            responsiveClass: false,
            autoplay: false,
            autoplayTimeout: 7000,
            autoplayHoverPause: true,
            smartSpeed: 500,
            items: 4,
            nav: false,
            slideBy: 1,
            dots: false,
            center: false,
        });
        if (window.innerWidth < 501) {
            $(".advantages_mob").show()
        } else {
            $(".advantages_mob").hide()
        }
        ;


    }());


    // new Desktop Slider
    (function () {
        $("#js-desktop-slider2").owlCarousel({
            loop: true,
            responsiveClass: true,
            autoplay: true,
            autoplayTimeout: 7000,
            autoplayHoverPause: true,
            smartSpeed: 500,
            items: 1,
            nav: true,
            navText: ['<span class="_ico-arr-left"></span>',
                '<span class="_ico-arr-right"></span>'],
            dots: true,
            animateOut: 'fadeOut'
        });

        var dots = jQuery(".owl-slider2_blk .owl-dots");
        dots.css({'margin-left': -(dots.width() / 2)});
    }());

    //yandex maps api
    if ($('div').is('._y-map')) {
        ymaps.ready(init);
        var myMap,
            myPlacemark;

        function init() {
            myMap = new ymaps.Map("y-map", {
                center: [53.952751, 27.692689],
                zoom: 16
            });
            myPlacemark = new ymaps.Placemark([53.952751, 27.692689], {
                hintContent: 'Постройка.бел',
                balloonContent: 'Магазин стройматериалов Постройка.бел (Беларусь, Минск, Уручская улица, 12А)'
            }, {
                preset: 'islands#dotIcon',
                iconColor: '#f66625'
            });
            myMap.geoObjects.add(myPlacemark);
        }
    }

    // For Google rating in footer
    $("#google-reviews").googlePlaces({
        placeId: 'ChIJsx-0uTfJ20YRsEsw3j3As5k',
        render: ['schema'],
        schema: {
            displayElement: '#schema',
            beforeText: 'Рейтинг',
            middleText: 'на основе',
            afterText: 'отзывов пользователей <a href="https://goo.gl/maps/aUQtfVcPns42" rel="nofollow" target="_blank">Google</a>',
            type: 'Store'
        }
    });

    //sticky cart
    var stickyOffsetDesktop = $('.main-menu_blk').offset().top;
    var stickyOffsetMob = $('.header_panel').offset().top + $('.header_panel').height() + 10;

    function stickyCart(stickyOffset) {
        var sticky_cart_blk = $('.sticky_cart .cart_blk'),
            sticky = $('.sticky_cart'),
            scroll = $(window).scrollTop();
        if (scroll >= stickyOffset) {
            sticky.addClass('cart_visible');
            sticky_cart_blk.addClass('fadeInDown');
        } else {
            sticky.removeClass('cart_visible');
            sticky_cart_blk.removeClass('fadeInDown');
        }
    }

    $(window).scroll(function () {
        if (window.innerWidth > 860) {
            stickyCart(stickyOffsetDesktop)
        }
        // else stickyCart(stickyOffsetMob)
    });

    //catalog menu dropdown on desktop
    if (windowWidth > MQ.mobil) {
        var cat = $(".category-menu_desktop");
        var menuItemCatalog = $(".main_nav li:first-child a");

        var delay = 300;
        var setI;

        function addClass() {
            cat.hasClass('changeZIndex') ? null : cat.delay(1000).addClass('changeZIndex');
        }

        menuItemCatalog.mouseenter(function (e) {
            e.stopPropagation();
            setI = setTimeout(addClass, delay);
        })
        menuItemCatalog.mouseleave(function (e) {
            clearTimeout(setI);
        })
        $(document).mouseover(function (e) {
            clearTimeout(setI);
            if ((!cat.is(e.target)) && (!menuItemCatalog.is(e.target))
                && (cat.has(e.target).length === 0) && (menuItemCatalog.has(e.target).length === 0)) {
                cat.removeClass('changeZIndex');
            }
        });
    }

    //set cookie
    function setCookie(name, value, days) {
        var expires;
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        } else {
            expires = "";
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    // Read cookie
    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    }

    //info cart pop-up
    var totalCart = $(".summ")[0],
        sticky_cart_info = $(".sticky_cart_info");
    $(".send-cart").on('click', function () {
        setTimeout(function () {
            var parseTotal = (totalCart.textContent.split(',')[0]).replace(/\s+/g, '');
            if (parseTotal > 800 && !readCookie('cartInfoShow')) {
                sticky_cart_info.addClass('info_visible');
                setCookie('cartInfoShow', 'true', false);
                var widthCart = $('.sticky_cart .cart_blk').outerWidth();
                $('.sticky_cart .cart-info').outerWidth(widthCart);
            }
        }, 500);
    });
    $(".cart-info_close-btn").on('click', function () {
        sticky_cart_info.removeClass('info_visible');
    });

    // show/hide filter on the catalog page (mob.ver.)
    $('.btn-filter-catalog_blk').click(function () {
        $('.wrapper-filter-catalog_blk').slideToggle();
    })

    //filter page catalog
    var filter_seo_block_right = $(".filter_seo_block .right-part");
    if ($(".secret_btn").is(':visible')) {
        filter_seo_block_right.height($(".filter_blk").height()).css('padding-top', '15px');
    }
    /*else {
        filter_seo_block_right.outerHeight(auto);
    }*/

    if ($('.filter_blk').length == 0) {
         // $(".seo-catalog_blk, .filter_seo_block .right-part, .filter_seo_block").addClass('withoutFilter');

        if ($('.left-part').children('div').length == 0) {
            $(".filter_seo_block.withoutFilter .left-part").css('width', '0');
            $(".filter_seo_block.withoutFilter").css('justify-content', 'unset');
        }
    }

    //for delivery (order)
    $('#address').on('change, blur', function () {
        if ($('#infoDeliveryZone').text().length == 0) {
            $('.openDeliveryMap').css('display', 'inline-block')
        } else $('.openDeliveryMap').css('display', 'none')

        if ($(this).val().length < 2) {
            $('.wrapper-address #infoDeliveryZone').removeClass('zoneDefined');
        }
    });
    $('#address').on('focus', function () {
        $('.zoneDefined').empty();
    });


    //delivery show text
    $('.openDeliveryMap').click(function () {
        if ($(this).parent().hasClass('wrapper-address')) {
            $('.delivery-map_blk span').css('display', 'inline');
        } else {
            $('.delivery-map_blk span').css('display', 'none')
        }
    });


    //validation for search form
    $('#find-form').on('submit', function () {
        event.preventDefault();
        if (validateNotFoundForm()) {
            return false;
        }

        var dataForm = {
            name: $('#not_found_form_name').val(),
        };

        $.ajax({
            dataType: "json",
            type: "POST",
            url: '/api/not_found_action',
            data: JSON.stringify(dataForm),

            error: function (data) {
                $('.form-msg').html('<span>* </span>' + data.responseJSON.errors.name).addClass('error');
            },
            success: function (data) {
                $('.form-msg').html('К сожалению, у нас такого товара нет, либо название введено с орфографическими ошибками. Но мы учтем Ваше пожелание. Спасибо большое!').addClass('send');
                $('#not_found_form_name').val('');
                $('.control-submit').css('display', 'none');
            }
        });

        function validateNotFoundForm() {
            $('.form-msg').html('').removeClass('error, send');

            var nff_name = $('#not_found_form_name');
            if (nff_name.val().length < 1) {
                var v_msd = true;
                $('.form-msg').html('<span>*</span> Значение не должно быть пустым').addClass('error');
            }
            return v_msd;
        }
    });

    // mob menu categories
    (function () {
        var item = $('.categories_nav_mob-menu > li'),
            sub = $(item).find('.subnav'),
            item2 = $('.second-level_mob li'),
            sub2 = $(item2).find('._ico-arr-right'),
            headItem = $('.mob-menu_categories__head'),
            sub3 = $(item).find('a.lonely-a'),
            iconMenu = $('.head-links_mobile__column-icon a');
        sub3.each(function () {
            var f = $(this).parent();
            f.toggleClass("lonely-li");
        })

        var itemA = $('.categories_nav_mob-menu a.group');
        itemA.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
        sub2.each(function () {
            var tiger = $(this).parent();
            var tigerIcon = $(this).parent().find('span');
            tigerIcon.show();
            tiger.on('click', function (event) {
                var target1 = $(event.target);
                $(item2).toggleClass('open');
                event.preventDefault();
                event.stopPropagation();
            });
        });

        sub.each(function () {
            var trigger = $(this).parent();
            var triggerIcon = $(this).parent().find('.mob-menu_categories__arr-icon span');
            triggerIcon.show();

            trigger.on('click', function (event) {
                var target = $(event.target);

                var close_menu = function () {
                    $('.mob-menu_categories, #swipe-menu_btn').removeClass('active');
                    $('.mobil-phones_popup-btn').removeClass('next');

                    $(this).toggleClass('open');
                    $(this).siblings().hasClass('open') ? $(this).siblings().removeClass('open') : null;
                };

                if (target.hasClass('group-child')) {
                    setTimeout(close_menu, 1000);
                } else if (target.hasClass('group-child' && 'subgroup-child')) {
                    setTimeout(close_menu, 1000);
                } else {
                    $(this).toggleClass('open');
                    $(this).siblings().hasClass('open') ? $(this).siblings().removeClass('open') : null;
                }

                if ($(this).hasClass('hover')) {
                    $(this).removeClass('hover');
                    $(this).removeClass('open');
                }
            });
        });

        headItem.click(function () {
            $(this).toggleClass('open_nav');
            $(this).parent().next('.vertical_nav').toggleClass('open_nav');
        });

        iconMenu.click(function () {
            $(this).siblings().removeClass('active');
            $(this).toggleClass('active');
            if ($(this).next('a')) {
                $(this).next('a').toggleClass('next');
            }
        });

        $('#swipe-menu_btn, #head-close-icon').click(function () {
            $(".c-search-widget.show768[data-type='mobile']").toggleClass('hiden');
        });

        $('#head-close-icon').click(function () {
            iconMenu.removeClass('next');
        });

        if ($(window).width() < 860) {
            $('._ico-close').click(function () {
                $('.mobil-phones_popup-btn').removeClass('active');
                iconMenu.removeClass('next');
            });

            if ((location.href.indexOf('/cart') != -1)) {
                $('.head-links_mobile__column-icon .cart').addClass('active');
            }
        }
    })();

    // sticky mob header
    // hide/show search mobile header form
    var mobHeaderHeight = $('.header_panel .show768').height();

    function mobHeader(mobHeaderHeight) {
        var searchMobHeaderBlock = $('.c-search-widget[data-type="mobile"]'),
            searchMobHeaderForm = $('.c-search-widget[data-type="mobile"] form'),
            scroll = $(window).scrollTop(),
            panel = $('.header_panel > ._container');

        if (scroll >= mobHeaderHeight) {
            searchMobHeaderBlock.addClass('form_hiden');
            panel.addClass('addSticky');
            searchMobHeaderForm.removeClass('fadeInDown');
        } else {
            searchMobHeaderBlock.removeClass('form_hiden');
            searchMobHeaderForm.addClass('fadeInDown');
            panel.removeClass('addSticky');
        }
    }

    $(window).scroll(function () {
        if ($(window).width() < 860) {
            mobHeader(mobHeaderHeight)
        }
    });

    $(document).ready(function () {
        $("#fl_inp").change(function () {
            let filename = $(this).val().replace(/.*\\/, "");
            $("#fl_nm").html(filename);
        });
        $("#fl_inp1").change(function () {
            let filename1 = $(this).val().replace(/.*\\/, "");
            $("#fl_nm1").html(filename1);
        });

    });



    // "Brands" page
    (function () {
        $('.brand-scroll').click(function () {
            let href = $(this).attr('href');
            document.querySelector(href).scrollIntoView({behavior: "smooth"});
            return false;
        });
    }());

    (function () {
            $.ajax({
                url: '/act',
                type: 'POST',
                data: {test: 'test'},
                success: function (data) {

                    setInterval(function (){
                   let city_top_count = data['cities_top'].length;
                   let city_low_count = data['cities_low'].length;
                   let page_count = data['pages'].length;
                   let page_array = data['pages'][Math.floor(Math.random() * page_count)];
                   let city = data['cities_top'][Math.floor(Math.random() * city_top_count)];
                   let city_low = data['cities_low'][Math.floor(Math.random() * city_low_count)];

                   if (Math.floor(Math.random() * 10) > 7){
                       city = city_low;
                   }

                   let url = page_array['url'];
                   let title = page_array['title'];
                   console.log(page_array)
                    let acts = [
                        `Пользователь из  ${city}  положил в корзину <a href=${url} > ${title}  </a><i class="_ico-cart ls"></i>`,
                        `Пользователь из  ${city}  ищет <a href=${url} > ${title}  </a><i class="_ico-cart ls"></i>`,
                        `Пользователь из  ${city}  просматривает <a href=${url} > ${title}  </a><i class="_ico-cart ls"></i>`,
                        `Пользователь из  ${city}  заказал <a href=${url} > ${title}  </a><i class="_ico-cart ls"></i>`,
                    ];

                    let random_act = Math.floor(Math.random() * acts.length);
                    $('#akt_user').html(acts[random_act])
                        // console.log(randomInteger(5, 10) * 1000);
                    }, randomInteger(8, 15) * 1000);

                    // $('#akt_user').html(data);
                }
            })
    }());

    $('#sync2 .owl-next span').addClass('_ico-arr-right');
    $('#sync2 .owl-prev span').addClass('_ico-arr-left');
    $('#sync2 .owl-nav.disabled').removeClass('disabled');

});

function randomInteger(min, max) {
    let rand = min + Math.random() * (max + 1 - min);
    return Math.floor(rand);
}

var sync1 = $(".pr-slider");
var sync2 = $(".navigation-thumbs");

var thumbnailItemClass = '.owl-item';

var slides = sync1.owlCarousel({
    center: false,
    video: true,
    startPosition: 0,
    items: 1,
    loop: true,
    margin: 10,
    autoplay: false,
    nav: false,
    dots: true,
}).on('changed.owl.carousel', syncPosition);

function syncPosition(el) {
    $owl_slider = $(this).data('owl.carousel');
    var loop = $owl_slider.options.loop;

     if(loop) {
         var count = el.item.count - 1;
         var current = Math.round(el.item.index - (el.item.count / 2) - .5);
         if (current < 0) {
             current = count;
         }
         if (current > count) {
             current = 0;
         }
     } else {
             var current = el.item.index;
     }

    var owl_thumbnail = sync2.data('owl.carousel');
    var itemClass = "." + owl_thumbnail.options.itemClass;


    var thumbnailCurrentItem = sync2
        .find(itemClass)
        .removeClass("synced")
        .eq(current);

    thumbnailCurrentItem.addClass('synced');

    if (!thumbnailCurrentItem.hasClass('active')) {
        var duration = 300;
        sync2.trigger('to.owl.carousel',[current, duration, true]);
    }
}
var thumbs = sync2.owlCarousel({
    center: false,
    startPosition: 0,
    items: 3,
    loop: false,
    margin: 10,
    autoplay: false,
    nav: true,
    dots: false,
    onInitialized: function (e) {
        var thumbnailCurrentItem =  $(e.target).find(thumbnailItemClass).eq(this._current);
        thumbnailCurrentItem.addClass('synced');
    },
})
    .on('click', thumbnailItemClass, function(e) {
        e.preventDefault();
        var duration = 100;
        var itemIndex =  $(e.target).parents(thumbnailItemClass).index();
        sync1.trigger('to.owl.carousel',[itemIndex, duration, true]);
    }).on("changed.owl.carousel", function (el) {
        var number = el.item.index;
        $owl_slider = sync1.data('owl.carousel');
        $owl_slider.to(number, 100, true);
    });

(function($) {
    $(function() {

        $('ul.main-tabs_title').on('click', 'li:not(.active)', function() {
            $(this)
                .addClass('active').siblings().removeClass('active')
                .closest('div.main-tabs').find('div.main-tabs_content').removeClass('active').eq($(this).index()).addClass('active');
        });

    });
})(jQuery);