<?php echo head(array('title' => 'Library of Congress Suggest')); ?>
<script type="text/javascript" charset="utf-8">
//<![CDATA[
jQuery(document).ready(function() {
    jQuery('#element-id').change(function() {
        jQuery.post(
            <?php echo js_escape(url('lc-suggest/index/suggest-endpoint')); ?>, 
            {element_id: jQuery('#element-id').val()}, 
            function(data) {
                jQuery('#suggest-endpoint').val(data);
            }
        );
    });
});
//]]>
</script>
<?php echo flash(); ?>
<form method="post" action="<?php echo url('lc-suggest/index/edit-element-suggest'); ?>">
<section class="seven columns alpha">
    <div class="field">
        <div id="element-id-label" class="two columns alpha">
            <label for="element-id">Element</label>
        </div>
        <div class="inputs five columns omega">
            <?php echo $this->formSelect('element_id', null, array('id' => 'element-id'), $this->form_element_options) ?>
            <p class="explanation">Select an element to assign it a Library of 
            Congress authority/vocabulary. Elements already assigned an 
            authority/vocabulary are marked with an asterisk (*).</p>
        </div>
    </div>
    <div class="field">
        <div id="suggest-endpoint-label" class="two columns alpha">
            <label for="suggest-endpoint">Authority/Vocabulary</label>
        </div>
        <div class="inputs five columns omega">
            <?php echo $this->formSelect('suggest_endpoint', null, array('id' => 'suggest-endpoint'), $this->form_suggest_options); ?>
            <p class="explanation">Enter a Library of Congress authority/vocabulary 
            to enable the autosuggest feature for the above element. To disable 
            the feature just deselect the option. For more information about the 
            authorities and vocabularies available at the Library of Congress see 
            <a href="http://id.loc.gov" target="_blank">http://id.loc.gov</a></p>
        </div>
    </div>
</section>
<section class="three columns omega">
    <div id="edit" class="panel">
        <?php echo $this->formSubmit('edit-element-suggest', 'Edit Suggest', array('class' => 'submit big green button')); ?>
    </div>
</section>
</form>
<section class="ten columns alpha">
    <h2>Current Assignments</h2>
    <?php if ($this->assignments): ?>
    <table>
        <thead>
        <tr>
            <th>Element Set</th>
            <th>Element</th>
            <th>Authority/Vocabulary</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->assignments as $assignment): ?>
        <tr>
            <td><?php echo $assignment['element_set_name']; ?></td>
            <td><?php echo $assignment['element_name']; ?></td>
            <td><?php echo $assignment['authority_vocabulary']; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>There are no suggest assignments.</p>
    <?php endif; ?>
</section>
<?php echo foot(); ?>