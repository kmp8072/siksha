<?php
require_once(__DIR__.'/../../config.php');

// license controller
$l_controller = new \theme_remui\controller\license_controller();
$plugin_slug = 'remui';

// handle license status change on form submit
$l_controller->addData();

// admin_externalpage_setup('External Page');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('licensesettings', 'theme_remui'));
$PAGE->set_heading(get_string('licensesettings', 'theme_remui'));
$PAGE->set_url($CFG->wwwroot.'/theme/remui/remui_license.php');

echo $OUTPUT->header();

// Get License key
$license_key = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $plugin_slug .'_license_key'), IGNORE_MISSING);


// Get License Status
$status = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $plugin_slug . '_license_status'), IGNORE_MISSING);


// Get renew link
$renew_link = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'wdm_'.$plugin_slug.'_product_site'), IGNORE_MISSING);

//Show proper reponse to user on license activation/deactivation
if (isset($_POST['edd_' . $plugin_slug .'_license_key']) && empty($_POST['edd_' . $plugin_slug .'_license_key'])) {
    //If empty, show error message
    echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Success</h4>'.get_string("enterlicensekey", "theme_remui").'
    </div>';

} elseif (!empty($_POST['edd_' . $plugin_slug .'_license_key'])) {

    if ($status !== false && $status == 'valid' && isset($_POST['edd_' . $plugin_slug . '_license_activate'])) {

        //Valid license key
        echo '<div class="alert alert-success">
           <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
           <h4><i class="icon fa fa-check"></i> Success</h4>'.get_string("licensekeyactivated", "theme_remui").'
        </div>';

    } elseif ($status !== false && $status == 'expired') { //Expired license key

    echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("licensekeyhasexpired","theme_remui").'
    </div>';

    } elseif ($status !== false && $status == 'disabled') { //Disabled license key
            echo '<div class="alert alert-danger">
               <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
               <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("licensekeyisdisabled", "theme_remui").'
            </div>';

    } elseif ($status == 'invalid') { //Invalid license key
        echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("entervalidlicensekey", "theme_remui").'
    </div>';

    } elseif ($status == 'site_inactive') { //Site is inactive
        echo '<div class="alert alert-danger">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
              <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("siteinactive", "theme_remui").'
            </div>';

    } elseif ($status == 'deactivated') { //Site is inactive
            echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("licensekeydeactivated", "theme_remui").'
       </div>';

    } elseif ($status == 'no_response' || (isset($_POST['edd_' . $plugin_slug . '_license_deactivate']) && $status == 'valid')) { //Site is inactive

        echo '<div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("noresponsereceived","theme_remui").'
            </div>';
    }
}
?>
        <div class="license-box box box-warning">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo get_string('edwiserremuilicenseactivation', 'theme_remui') ?></h3>
            </div>

            <!-- /.box-header -->
            <div class="box-body">

              <form method="post" class="form-horizontal" action="">

              <?php

                if ($status=="valid") { ?>

                    <div class="form-group has-success">

                      <label class="control-label text-black col-sm-3"><?php echo get_string('licensekey', 'theme_remui') ?>:</label>

                      <div class="col-sm-9">
                      <?php echo "<input id='edd_{$plugin_slug}_license_key' class='form-control' name='edd_{$plugin_slug}_license_key' type='text' class='regular-text' value='{$license_key}' placeholder='Enter license key...' readonly/>"; ?>
                      </div>
                    </div>

                <?php } else if ($status=="expired") { ?>

                    <div class="form-group has-error">

                        <label class="control-label text-black col-sm-3"><?php echo get_string('licensekey', 'theme_remui') ?>:</label>

                        <div class="col-sm-9">
                        <?php echo "<input id='edd_{$plugin_slug}_license_key' class='form-control' name='edd_{$plugin_slug}_license_key' type='text' class='regular-text' value='{$license_key}' placeholder='Enter license key...' readonly/>"; ?>
                        </div>
                    </div>

                <?php } else { ?>

                    <div class="form-group has-error">

                        <label class="control-label text-black col-sm-3"><?php echo get_string('licensekey', 'theme_remui') ?>:</label>

                        <div class="col-sm-9">
                        <?php echo "<input id='edd_{$plugin_slug}_license_key' class='form-control'  name='edd_{$plugin_slug}_license_key' type='text' class='regular-text' value='{$license_key}' placeholder='Enter license key...' />"; ?>
                        </div>
                    </div>

                <?php } ?>

                <div class="form-group">
                    <?php
                        echo '<label class="control-label col-sm-3">'.get_string('licensestatus', 'theme_remui').':</label>';

                        echo '<div class="col-sm-9">';

                        $status_text_active = get_string('active', 'theme_remui');
                        $status_text_active_text = "<p style='color:green;'>{$status_text_active}</p>";
                        $status_text_inactive = get_string('notactive', 'theme_remui');
                        $status_text_inactive_text = "<p style='color:red;'>{$status_text_inactive}</p>";
                        $status_text_expired = get_string('expired', 'theme_remui');
                        $status_text_expired_text = "<p style='color:red;'>{$status_text_expired}</p>";

                        if ($status !== false && $status == 'valid') {
                            echo $status_text_active_text;
                        } elseif ($status == 'site_inactive') {
                            echo $status_text_inactive_text;
                        } elseif ($status == 'expired') {
                            echo $status_text_expired_text;
                        } elseif ($status == 'invalid') {
                            echo $status_text_inactive_text;
                        } else {
                            echo $status_text_inactive_text;
                        }

                        echo '</div>';
                    ?>
                </div>

                <div class="form-group">
                    <?php

                        $activate_license_text = get_string('activatelicense', 'theme_remui');
                        $deactivate_license_text = get_string('deactivatelicense', 'theme_remui');
                        $renew_license_text = get_string('renewlicense', 'theme_remui');

                        echo "<label class='control-label col-sm-3'>{$activate_license_text}:</label>";

                        echo '<div class="col-sm-9">';

                        if ($status !== false && $status == 'valid') {
                            echo "<input type='submit' class='btn btn-primary text-white'  style='color:white;' name='edd_{$plugin_slug}_license_deactivate' value='{$deactivate_license_text}'/>";
                        } elseif ($status == 'expired') {
                            echo "<input type='submit' class='btn btn-primary' style='color:white;' name='edd_{$plugin_slug}_license_deactivate' value='{$deactivate_license_text}'/>&nbsp&nbsp";

                            echo '<input type="button" class="btn btn-primary" style="color:white;" name="edd_'.$plugin_slug.'_license_renew" value="'.$renew_license_text.'" onclick="window.open(\''.$renew_link.'\');">';
                        } else {
                            echo "<input type='submit' class='btn btn-primary' style='color:white;' name='edd_{$plugin_slug}_license_activate' value='{$activate_license_text}'/>";
                        }

                        echo '</div>';
                    ?>
                </div>

              </form>
            </div>
            <!-- /.box-body -->
          </div>

<?php

echo $OUTPUT->footer();