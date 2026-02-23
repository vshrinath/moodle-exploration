define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    return {
        init: function(jobid, sesskey, checkurl) {
            var interval = setInterval(function() {
                $.ajax({
                    url: checkurl,
                    data: {
                        checkjob: jobid,
                        sesskey: sesskey
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            clearInterval(interval);
                            Notification.exception(new Error(data.error));
                            return;
                        }

                        var $bar = $('#sceh-import-bar');
                        var $text = $('#sceh-import-status-text');

                        if (data.status === 'processing') {
                            $bar.css('width', '50%').text('Processing...');
                            if (data.message) {
                                $text.text(data.message);
                            }
                        } else if (data.status === 'completed') {
                            clearInterval(interval);
                            $bar.removeClass('progress-bar-animated bg-primary').addClass('bg-success').css('width', '100%').text('Completed!');
                            $text.html('Import finished successfully. <a href="' + data.url + '" class="btn btn-success btn-sm ml-2">View Course</a>');
                            
                            // Auto redirect after 2 seconds if result is good.
                            setTimeout(function() {
                                window.location.href = data.url;
                            }, 2000);

                        } else if (data.status === 'failed') {
                            clearInterval(interval);
                            $bar.removeClass('progress-bar-animated bg-primary').addClass('bg-danger').css('width', '100%').text('Failed');
                            $text.addClass('text-danger').html('<strong>Error:</strong> ' + data.message);
                        }
                    }
                });
            }, 2000);
        }
    };
});
