function tasks_openlog($, p)
{
    var $div = $('#' + p.containerID);

    /*
     * Init
     */
    $div.nosTabs('update', {
        label       : 'Tasks Logs',
        url         : 'admin/novius_cloud_tasks/logs/openlog/' + p.taskLaunchID,
        iconUrl     : 'static/apps/novius_cloud_tasks/img/tasks-log-16.png',
        app         : true,
        iconSize    : 16,
        labelDisplay: true
    });

    var nextseeks = {};

    getLogContent();

    function getLogContent()
    {
        var url = p.appUrl + '/logs/ajaxLog/' + p.taskLaunchID + '?';
        $.each(nextseeks, function(name, seek)
        {
            url += 'seek_' + name + '=' + seek + '&';
        });

        $div.nosAjax({
            url     : url,
            dataType: 'json',
            type    : 'POST',
            success : function(json)
            {
                var mode = 'gzip';

                if (!json.error) {

                    $.each(json.logs, function(name, log)
                    {
                        // En mode "live", on reçoit des informations bloc par bloc (seek sur le fichier .live)
                        // On doit donc ajouter ces nouvelles infos à la fin.
                        if (log.mode == 'live') {
                            nextseeks[name] = log.nextseek || 0;
                            $div.find('.' + name + '_content').append(log.content);
                            mode = 'live';
                        } else {
                            $div.find('.' + name + '_content').html(log.content);
                        }
                    });

                    // On met a jour le statut
                    if (json.status) {
                        $div.find('.status').html('(' + json.status + ')');
                    }

                    // On relance dans 1s
                    if (mode == 'live') {
                        setTimeout(getLogContent, 1000)
                    }
                }
            }
        });
    }
}