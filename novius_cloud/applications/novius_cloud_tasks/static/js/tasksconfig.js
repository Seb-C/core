function tasksconfig($, p)
{
    var $div = $('#' + p.containerID);
    var $panel = $div.find('.rightpanel');

    /*
     * Init
     */
    $div.nosTabs('update', {
        label       : 'Tasks Config',
        url         : 'admin/novius_cloud_tasks/config',
        iconUrl     : 'static/apps/novius_cloud_tasks/img/tasks-config-32.png',
        app         : true,
        iconSize    : 32,
        labelDisplay: false
    });

    // Ajout de la toolbar, pour accéder aux logs
    $div.nosToolbar($('<a class="js_logs" href="">Accéder aux logs</a>'));

    $div.find('table.tasks_list').wijgrid({
        rendered            : function(args)
        {
            var $table = $(args.target);
            $table.find('tbody tr h3').each(function()
            {
                var $this = $(this);
                $this.closest('td').css({
                    borderRight: 0
                })
            });
        },
        selectionMode       : 'none',
        highlightCurrentCell: false,
        readAttributesFromData: true
    });

    /*
     * Logs d'une tache
     */
    $div.parent().find('a.js_logs').click(function(e)
    {
        e.preventDefault();

        $div.nosTabs('open', {
            url: p.appUrl+'/logs',
            iframe: false,
            label: '',
            labelDisplay: false,
            iconUrl: 'static/apps/novius_cloud_tasks/img/tasks-log-32.png',
            iconSize: 32
        });
    });

    /*
     * Programmation d'une nouvelle tache, ou modification d'une tache existante
     */
    $div.find('a.js_programmation').click(function(e)
    {
        e.preventDefault();

        var $this = $(this);
        var $tr = $this.closest('tr');

        // Impossible de programmer une tâche non valide !
        if ($tr.hasClass('error')) {
            alert($tr.attr('title'));
            return false;
        }

        // Quelle task ?
        var progID = $tr.attr('data-prog-id') || 0;
        var taskIdentifier = $tr.attr('data-task-identifier');
        if (!taskIdentifier) {
            $.nosNotify('data-task-identifier introuvable', 'error');
        }

        // Ouverture du panel
        $panel.html('').show();
        $.ajax({
            url     : p.appUrl + '/config/ajaxProgForm?progID=' + progID + '&taskIdentifier=' + taskIdentifier,
            dataType: 'html',
            success : function(html)
            {
                $panel.html(html);
            }
        });
    });

    $div.on('submit', 'form.js_programmation', function(e)
    {
        e.preventDefault();

        var $this = $(this);
        var progID = $this.attr('data-prog-id') || 0;
        var taskIdentifier = $this.attr('data-task-identifier');

        $div.nosAjax({
            url     : p.appUrl + '/config/ajaxProgSave?progID=' + progID + '&taskIdentifier=' + taskIdentifier,
            dataType: 'json',
            type    : 'POST',
            data    : $this.serialize(),
            success: function(json)
            {
                if (!json.error) {
                    $div.nosTabs('reload');
                    $.nosNotify('Programmation sauvegardée avec succès.');
                }
            }
        });
    });

    /*
     * Suppression d'une programmation
     */

    $div.find('a.js_suppression').click(function(e)
    {
        e.preventDefault();

        var $this = $(this);
        var $tr = $this.closest('tr');

        var progID = $tr.attr('data-prog-id') || 0;
        if (!progID) {
            $.nosNotify('data-prog-id introuvable', 'error');
        }

        if (!confirm('Etes-vous certain de supprimer cette tâche programmée ?')) {
            return false;
        }

        // Suppression !
        $div.nosAjax({
            url     : p.appUrl + '/config/ajaxProgDelete?progID=' + progID,
            dataType: 'json',
            success: function(json)
            {
                if (!json.error) {
                    $div.nosTabs('reload');
                    $.nosNotify('Programmation supprimée avec succès.');
                }
            }
        });
    });


    /*
     * Lancement manuel d'une tache
     */
    $div.find('a.js_lancement_manuel').click(function(e)
    {
        e.preventDefault();

        var $this = $(this);
        var $tr = $this.closest('tr');

        // Impossible de programmer une tâche non valide !
        if ($tr.hasClass('error')) {
            alert($tr.attr('title'));
            return false;
        }

        // Quelle task ?
        var taskIdentifier = $tr.attr('data-task-identifier');
        if (!taskIdentifier) {
            $.nosNotify('data-task-identifier introuvable', 'error');
        }

        // Lanchement !
        $div.nosAjax({
            url     : p.appUrl + '/config/ajaxLaunch?taskIdentifier=' + taskIdentifier,
            dataType: 'json',
            success: function(json)
            {
                $.nosNotify('Lancement de la tâche en cours...');
                openlog(json.token);
            }
        });

        function openlog(token, retry)
        {
            retry = retry || 3;

            // A partir du token, on récupère l'ID du lancement de la tâche
            $div.nosAjax({
                url     : p.appUrl + '/config/ajaxTaskLaunchFromToken/' + token,
                dataType: 'json',
                success : function(json)
                {
                    if (json.tala_id) {
                        // On a eu une réponse, on ouvre l'onglet
                        $div.nosTabs('open', {
                            url         : p.appUrl + '/logs/openlog/' + json.tala_id,
                            label       : '',
                            labelDisplay: true,
                            iconUrl     : 'static/apps/novius_cloud_tasks/img/tasks-log-16.png',
                            iconSize    : 32
                        });
                    } else if (retry > 0) {
                        // Pas de tala_id, la task n'est peut être pas encore lancé (asynchrone...)
                        // On attend un peu avant de recommencer (seulement si retry > 0)
                        setTimeout(function()
                        {
                            openlog(token, retry - 1);
                        }, 500);
                    }
                }
            });
        }
    });

    /*
     * Gestion du panel de droite
     */
    $div.on('click', '.js_close', function(e) {
        e.preventDefault();
        $panel.html('').hide();
    });
    $div.on('click', '.js_selectall', function(e) {
        e.preventDefault();
        $(this).closest('label').find('option').prop('selected', true);
    });
    $div.on('click', '.js_selectnone', function(e) {
        e.preventDefault();
        $(this).closest('label').find('option').prop('selected', false);
    });
}