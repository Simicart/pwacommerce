'use strict';
self.addEventListener(
    'install', function (event) {
    event.waitUntil(
        self.skipWaiting()
    );
    }
);

self.addEventListener(
    'activate', function (event) {
    var cacheWhitelist = ['v2'];

    event.waitUntil(
        caches.keys().then(
            function (keyList) {
            return Promise.all(
                keyList.map(
                    function (key) {
                    if (cacheWhitelist.indexOf(key) === -1) {
                    return caches.delete(key);
                    }
                    }
                )
            );
            }
        )
    );
    }
);

self.addEventListener(
    'fetch', function (event) {
    if (event.request.method !== 'POST' &&
        event.request.url.toString() &&
        event.request.url.toString().indexOf('/checkout/') === -1 &&
        event.request.url.toString().indexOf('/cart/') === -1 &&
        event.request.url.toString().indexOf('/key/') === -1) {
        event.respondWith(
            caches.match(event.request)
                .then(
                    function (response) {
                    if (response) {
                        return response;
                    }

                    var fetchRequest = event.request.clone();

                    return fetch(fetchRequest).then(
                        function (response) {
                            if (!response || response.status !== 200 || response.type !== 'basic') {
                                return response;
                            }

                            var responseToCache = response.clone();
                            caches.open('simipwa-cache')
                                .then(
                                    function (cache) {
                                    cache.put(event.request, responseToCache);

                                    }
                                );

                            return response;
                        }
                    );
                    }
                )
        );
    }
    }
);
self.addEventListener(
    'push', function (event) {
    var apiPath = './simipwa/index/message?endpoint=';
    event.waitUntil(
        registration.pushManager.getSubscription()
            .then(
                function (subscription) {
                if (!subscription || !subscription.endpoint) {
                    throw new Error();
                }

                apiPath = apiPath + encodeURI(subscription.endpoint);
                return fetch(apiPath)
                    .then(
                        function (response) {
                        if (response.status !== 200){
                            console.log("Problem Occurred:"+response.status);
                            throw new Error();
                        }

                        return response.json();
                        }
                    )
                    .then(
                        function (data) {
                        if (data.status == 0) {
                            console.error('The API returned an error.', data.error.message);
                            throw new Error();
                        }

                        //console.log(data);
                        var options = {};
                        var title = '';
                        var icon = data.notification.logo_icon;
                        if (data.notification.notice_title){
                            title = data.notification.notice_title;
                            var message = data.notification.notice_content;
                            var url = '/';
                            if (data.notification.notice_url) {
                                url = data.notification.notice_url;
                            }

                            if (data.notification.image_url){
                                options['image'] = data.notification.image_url;
                            }

                            var data = {
                                url: url
                            };
                            options = {
                                body : message,
                                icon: icon,
                                data: data
                            };
                        } else {
                            title = 'New Notification';
                            options = {
                                icon : icon,
                                data: {
                                    url: "/"
                                }
                            };
                        }

                        return self.registration.showNotification(title, options);
                        }
                    )
                    .catch(
                        function (err) {
                        console.log(err);
                        return self.registration.showNotification(
                            'New Notification', {
                            icon: icon,
                            data: {
                                url: "/"
                            }
                            }
                        );
                        }
                    );
                }
            )
    );
    }
);
self.addEventListener(
    'notificationclick', function (event) {
    event.notification.close();
    var url = event.notification.data.url;
    event.waitUntil(
        clients.matchAll(
            {
            type: 'window'
            }
        )
            .then(
                function (windowClients) {
                for (var i = 0; i < windowClients.length; i++) {
                    var client = windowClients[i];
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }

                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
                }
            )
    );
    }
);
