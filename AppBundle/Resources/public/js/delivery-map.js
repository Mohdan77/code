/**
 * Создание зон доставки(Yandex maps)
 * @Param ymaps - обькт карты
 * */
var mapContainer = $('#deliveryMap');

var getZones = function (map) {

    var zonesArr = map.data().zones.split('|'),
        prices = {},
        i;

    for (i = 0; i < zonesArr.length; i++) {
        prices['zone' + (i + 1)] = zonesArr[i]
    }

    return prices
};

// Цены
var prices = getZones(mapContainer);

// Как только будет загружен API и готов DOM, выполняем инициализацию
ymaps.ready(deliveryMap);

function deliveryMap() {
    'use strict';

    var deliveryMap,
        coord = [53.908219, 27.560456],
        select = document.querySelector('#delivery'),
        popupBtn = $('.openDeliveryMap'),
        popup = $('.delivery-map_popup'),
        popupClose = popup.find('._ico-close'),
        actionsFlag = false;

    var search = new ymaps.control.SearchControl({
        options: {
            boundedBy: [[52.629251495247345, 23.94252081933595], [54.588870459029536, 28.98524542871094]],
			// strictBounds: true
        }
    });

    var mapProp = function (box) {
        var headline = box.find('.blk_headline'),
            height = box.height() - (headline.height() + 20);
        box.find('#deliveryMap').height(height);
    };

    var mapShow = function () {
        mapProp(popup);
        popup.show(function () {
            mapProp($(this));
            if (!deliveryMap) {
                initMap();
            }
        });
    };

    $(window).resize(function () {
        mapProp(popup);
    });

    var autoOpenMap = (function () {
        var mark = window.location.href.split('#')[1];
        if (mark === 'openDeliveryMap') {
            mapShow();
        }
    })();

    // Закрытие попап
    function closeMap() {
        popup.hide();
        search.clear();
    }

    popupBtn.on('click', function () {
        if ($(this).closest('.checkout_section')) {
            actionsFlag = true;
        }
        mapShow();
        return false;
    });

    popupClose.on('click', function () {
        closeMap()
    });


    $('#purchase').change(function() {
        if ($(this).val() === 'order.purchase.delivery' && (!deliveryMap)) {

                actionsFlag = true;

            initMap();
        }
    });

    function initMap() {
        // Инциализация карты
        deliveryMap = new ymaps.Map('deliveryMap', {
            center: coord,
            zoom: 8,
            controls: ["zoomControl"]
        });

        deliveryMap.controls.add(search);

        var hintLayout = ymaps.templateLayoutFactory.createClass(
            '<div class="_hint">' +
            '<div class="text">{{properties.hintContent}} — {{properties.price}}</div>' +
            '<div class="weight">Вес, объём: 2.1т, 11м<sup>3</sup></div>' +
            '</div>', {

                getShape: function () {
                    var el = this.getElement(),
                        result = null;
                    if (el) {
                        var firstChild = el.firstChild;
                        result = new ymaps.shape.Rectangle(
                            new ymaps.geometry.pixel.Rectangle([
                                [0, 0],
                                [firstChild.offsetWidth, firstChild.offsetHeight]
                            ])
                        );
                    }
                    return result;
                }
            });

        // Создание коллекции
        var objCollectionList = new ymaps.GeoObjectCollection({}, {
            hintLayout: hintLayout,
            strokeWidth: 2,
            strokeColor: '#555',
            fillOpacity: 0.4,
            strokeOpacity: 0.5
        });

        DELIVERY_MAP_DATA.forEach(function (el) {
            // - Создаем новый обьект(Полигон)
            var polygon = new ymaps.Polygon(
                el.coords,
                el.properties,
                el.options
            );
            // - Добавляем обьект в коллекцию
            objCollectionList.add(polygon);
        });

        // Добавление коллекции на карту
        var myPolygon = deliveryMap.geoObjects.add(objCollectionList);


        var counter = 1;
        objCollectionList.each(function (obj) {
            obj.zoneIndex = counter++;
            var zone = 'zone' + obj.zoneIndex;

            obj.properties._data.price = prices[zone];


            if (actionsFlag) {
                select = $(select);
                obj.events.add('click', function () {
                    select.get(0).selectedIndex = obj.zoneIndex;
                    select.change();
                    closeMap();

                    $('#infoDeliveryZone').html($('#delivery option:eq(' + obj.zoneIndex + ')').text()).addClass('zoneDefined');
                    $('.openDeliveryMap').css('display', 'inline-block')

                });
            }
        });

        //select delivery zone
        $('#address').on('change, blur', function () {
            var valAddress = $('#address').val();
            if(valAddress) {
                var myGeocoder = ymaps.geocode(valAddress);
                var res = myGeocoder.then(
                    function (result) {
                        $('#infoDeliveryZone').text('');

                        var coordinates = result.geoObjects.get(0).geometry.getCoordinates();
                        $('#delivery option:eq(0)').prop('selected', 'true');
                        objCollectionList.each(function (el) {
                            if (el.geometry.contains(coordinates)) {
                                var foundZone = el.properties._data.id;
                                var selectOption= $('#delivery option[value$=' + foundZone + ']').prop('selected', 'true').change();
                                $('#infoDeliveryZone').html(selectOption.text());
                                $('.wrapper-address #infoDeliveryZone').addClass('zoneDefined');
                            }
                        })
                        if($('#infoDeliveryZone').text().length == 0) {
                            $('.openDeliveryMap').css('display', 'inline-block');
                            $('.wrapper-address #infoDeliveryZone').removeClass('zoneDefined');
                        } else  $('.openDeliveryMap').css('display', 'none')
                    },
                    function (err) {
                        console.log('error');
                    }
                );
            }
        })

    }

}


ymaps.ready(suggestAddress);
// show tips - v1 - Подключаем поисковые подсказки к полю ввода.

// function suggestAddress() {
//     if(document.querySelector('#address')) {
//         new ymaps.SuggestView('address', {
//             provider: {
//                 suggest:(function(request, options){
//                     return  ymaps.suggest("Беларусь" + ", " + request);
//                 })
//             }
//         });
//     }
// }

// show tips - v2 - Подключаем поисковые подсказки к полю ввода с фильтром.
var provider = {
    suggest: function (request, options) {
        var arrayResult = [];
        var sug = ymaps.suggest("Беларусь" + ", " + request)
            .then(items => {
                items.map(function(item) {
                    if (item.displayName.indexOf('река') == -1 && item.displayName.indexOf('станция') == -1) {
                        var nemName = item.displayName.replace(', Беларусь', '');
                        arrayResult.push({displayName: nemName, value: nemName});
                    }
                });
            }).then(function () {
                return ymaps.vow.resolve(arrayResult);
            });
        return sug;
    }
};

function suggestAddress() {
    if(document.querySelector('#address')) {
        var suggestView = new ymaps.SuggestView('address', {provider: provider});

        var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        if (isMobile) {
            suggestView.events.add('select', function () {
                document.getElementById('address').blur();
            })
        }
    }
}