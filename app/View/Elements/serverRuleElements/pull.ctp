<div style="display: flex; flex-direction: column;" class="server-rule-container-pull">
    <?php if ($context == 'servers'): ?>
        <div class="alert alert-primary notice-pull-rule-fetched">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="<?= $this->FontAwesome->getClass('spinner') ?> fa-spin"></i>
            <?= __('Organisations and Tags are being fetched from the remote server.') ?>
        </div>
        <div class="alert alert-success hidden notice-pull-rule-fetched">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= __('Organisations and Tags have been fetched from the remote server.') ?>
        </div>
        <div class="alert alert-warning hidden notice-pull-rule-fetched">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= __('Issues while trying to fetch Organisations and Tags from the remote server.') ?>
            <div><strong><?= __('Reason:') ?></strong></div>
            <div><pre class="reason" style="margin-bottom: 0;"></pre></div>
        </div>
    <?php endif; ?>
    <?php
        $tagAllowRules = [];
        $tagBlockRules = [];
        $orgAllowRules = [];
        $orgBlockRules = [];
        $ruleUrlParams = [];
        if (!empty($server['Server']['pull_rules'])) {
            $tagRules = json_decode($server['Server']['pull_rules'], true);
            $tagAllowRules = $tagRules['tags']['OR'];
            $tagBlockRules = $tagRules['tags']['NOT'];
            $orgAllowRules = $tagRules['orgs']['OR'];
            $orgBlockRules = $tagRules['orgs']['NOT'];
            $ruleUrlParams = json_decode($tagRules['url_params'], true);
        }
    ?>
    <?php
        echo $this->element('serverRuleElements/rules_widget', [
            'scope' => 'tag',
            'scopeI18n' => __('tag'),
            'technique' => 'pull',
            'allowEmptyOptions' => true,
            'options' => $allTags,
            'optionNoValue' => true,
            'initAllowOptions' => $tagAllowRules,
            'initBlockOptions' => $tagBlockRules
        ]);
    ?>

    <div style="display: flex;">
        <h4 class="bold green" style=""><?= __('AND');?></h4>
        <h4 class="bold red" style="margin-left: auto;"><?= __('AND NOT');?></h4>
    </div>

    <?php
        echo $this->element('serverRuleElements/rules_widget', [
            'scope' => 'org',
            'scopeI18n' => __('org'),
            'technique' => 'pull',
            'allowEmptyOptions' => true,
            'options' => $allOrganisations,
            'optionNoValue' => true,
            'initAllowOptions' => $orgAllowRules,
            'initBlockOptions' => $orgBlockRules
        ]);
    ?>

    <div style="display: flex;">
        <h4 class="bold green" style=""><?= __('AND');?></h4>
    </div>

    <div style="display: flex; flex-direction: column;">
        <div class="bold green">
            <?= __('Additional sync parameters (based on the event index filters)');?>
        </div>
        <div style="display: flex;">
            <textarea style="width:100%;" placeholder='{"timestamp": "30d"}' type="text" value="" id="urlParams" required="required" data-original-title="" title="" rows="3"
            ><?= json_encode(h($ruleUrlParams), JSON_PRETTY_PRINT) ?> </textarea>
        </div>
    </div>
</div>

<?php
echo $this->element('genericElements/assetLoader', array(
    'js' => array(
        'codemirror/codemirror',
        'codemirror/modes/javascript',
        'codemirror/addons/closebrackets',
        'codemirror/addons/lint',
        'codemirror/addons/jsonlint',
        'codemirror/addons/json-lint',
    ),
    'css' => array(
        'codemirror',
        'codemirror/show-hint',
        'codemirror/lint',
    )
));
?>

<script>
var cm;
$(function() {
    var serverID = "<?= isset($id) ? $id : '' ?>"
    <?php if ($context == 'servers'): ?>
    addPullFilteringRulesToPicker()
    <?php endif; ?>
    setupCodeMirror()

    function addPullFilteringRulesToPicker() {
        var $rootContainer = $('div.server-rule-container-pull')
        var $pickerTags = $rootContainer.find('select.rules-select-picker-tag')
        var $pickerOrgs = $rootContainer.find('select.rules-select-picker-org')
        if (serverID !== "") {
            $pickerOrgs.parent().children().prop('disabled', true)
            $pickerTags.parent().children().prop('disabled', true)
            getPullFilteringRules(
                function(data) {
                    addOptions($pickerTags, data.tags)
                    addOptions($pickerOrgs, data.organisations)
                    $('div.notice-pull-rule-fetched.alert-success').show()
                },
                function(errorMessage) {
                    showMessage('fail', '<?= __('Could not fetch remote sync filtering rules.') ?>');
                    $('div.notice-pull-rule-fetched.alert-warning').show().find('.reason').text(errorMessage)
                    $pickerTags.parent().remove()
                    $pickerOrgs.parent().remove()
                },
                function() {
                    $('div.notice-pull-rule-fetched.alert-primary').hide()
                    $pickerTags.parent().children().prop('disabled', false).trigger('chosen:updated')
                    $pickerOrgs.parent().children().prop('disabled', false).trigger('chosen:updated')
                },
            )
        } else {
            $('div.notice-pull-rule-fetched.alert-warning').show().find('.reason').text('<?= __('The server must first be saved in order to fetch remote synchronisation rules.') ?>')
            $pickerTags.parent().remove()
            $pickerOrgs.parent().remove()
            $('div.notice-pull-rule-fetched.alert-primary').hide()
        }
    }

    function getPullFilteringRules(cb, fcb, acb) {
        $.getJSON('/servers/queryAvailableSyncFilteringRules/' + serverID, function(availableRules) {
            cb(availableRules)
        })
        .fail(function(jqxhr, textStatus, error) {
            fcb(jqxhr.responseJSON.message !== undefined ? jqxhr.responseJSON.message : textStatus)
        })
        .always(function() {
            acb()
        })
    }

    function addOptions($select, data) {
        data.forEach(function(entry) {
            $select.append($('<option/>', {
                value: entry,
                text : entry
            }));
        });
    }

    function setupCodeMirror() {
        var cmOptions = {
            mode: "application/json",
            theme:'default',
            gutters: ["CodeMirror-lint-markers"],
            lint: true,
            lineNumbers: true,
            indentUnit: 4,
            showCursorWhenSelecting: true,
            lineWrapping: true,
            autoCloseBrackets: true,
        }
        cm = CodeMirror.fromTextArea(document.getElementById('urlParams'), cmOptions);
        cm.on("keyup", function (cm, event) {
            $('#urlParams').val(cm.getValue())
        });
    }
})
</script>

<style>
div.server-rule-container-pull .CodeMirror {
    height: 100px;
    width: 100%;
    border: 1px solid #ddd;
}
</style>