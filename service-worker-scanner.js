self.addEventListener('beforeinstallprompt', function (e) {
    return e.userChoice.then(function (choiceResult) {
        if (choiceResult.outcome == 'accepted') {
        } else {
        }
    });
});
self.addEventListener('fetch', function (event) {
});
