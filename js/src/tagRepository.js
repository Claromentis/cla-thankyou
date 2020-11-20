define(['jquery'], function ($) {
    var repository = function () {
    };

    repository.prototype.get = function (id, success, error) {
        var url = '/api/thankyou/v2/tags/';
        if (!Number.isNaN(id)) {
            url += id;
        }

        var ajaxArgs = {
            url: url
        };

        if (success) {
            ajaxArgs.success = success;
        }
        if (error) {
            ajaxArgs.error = error;
        }

        $.ajax(ajaxArgs);
    };

    repository.prototype.save = function (tag, success, error) {
        var url = '/api/thankyou/v2/tags';
        if (tag.hasOwnProperty('id')) {
            url += '/' + tag.id;
        }

        var ajaxArgs = {
            url: url,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(tag)
        };

        if (success) {
            ajaxArgs.success = success;
        }
        if (error) {
            ajaxArgs.error = error;
        }

        $.ajax(ajaxArgs);
    };

    repository.prototype.delete = function (id, success, error) {
        var ajaxArgs = {
            url: '/api/thankyou/v2/tags/' + id,
            type: 'DELETE',
            dataType: 'json',
            contentType: 'application/json'
        };

        if (success) {
            ajaxArgs.success = success;
        }
        if (error) {
            ajaxArgs.error = error;
        }

        $.ajax(ajaxArgs);
    };

    return new repository();
});