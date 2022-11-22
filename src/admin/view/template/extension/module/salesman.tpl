<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-module" data-toggle="tooltip" title="<?php echo $button_save ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel ?>" data-toggle="tooltip" title="<?php echo $button_cancel ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
      </div>
      <h1><?php echo $heading_title ?></h1>
      <ul class="breadcrumb">
      <?php foreach ($breadcrumbs as $breadcrumb) { ?>
      <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
      <?php } ?>
      </ul>
    </div>
  </div>

  <div class="container-fluid">
    <?php if ($error_warning) { ?>
  <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php } ?>

  <?php if ($settings_success) { ?>
  <div class="alert alert-success alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $settings_success ?></div>
  <?php } ?>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $settings_edit ?></h3>
    </div>
    <div class="panel-body">
      <form action="<?php echo $action ?>" method="post" enctype="multipart/form-data" id="form-module" class="form-horizontal">

        <!-- Status -->
        <div class="form-group">
          <label class="col-sm-2 control-label" for="entry-status"><?= $help_status; ?></label>
          <div class="col-sm-10">
            <select name="module_salesman_status" id="entry-status" class="form-control">
              <?php if ($module_salesman_status) { ?>
                <option value="1" selected="selected"><?= $text_enabled; ?></option>
                <option value="0"><?= $text_disabled; ?></option>
                <?php } else {?>
                  <option value="1"><?= $text_enabled; ?></option>
                  <option value="0" selected="selected"><?= $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
        </div>

        <!-- API URL -->
        <div class="form-group required">
          <label class="col-sm-2 control-label" for="api-url">
            <?= $help_api_url; ?>
          </label>
          <div class="col-sm-10">
            <input
              type="text"
              name="module_salesman_api_url"
              value="<?= $module_salesman_api_url; ?>"
              autocomplete="off"
              id="api-url"
              class="form-control"
            />
          </div>
        </div>

        <!-- API Login -->
        <div class="form-group required">
          <label class="col-sm-2 control-label" for="api-login">
            <?= $help_api_login; ?>
          </label>
          <div class="col-sm-10">
            <input
              type="text"
              name="module_salesman_api_login"
              value="<?= $module_salesman_api_login; ?>"
              autocomplete="off"
              id="api-login"
              class="form-control"
            />
          </div>
        </div>

        <!-- API Token -->
        <div class="form-group required">
          <label class="col-sm-2 control-label" for="api-token">
            <?= $help_api_token; ?>
          </label>
          <div class="col-sm-10">
            <input
              type="text"
              name="module_salesman_api_token"
              value="<?= $module_salesman_api_token; ?>"
              autocomplete="off"
              id="api-token"
              class="form-control"
            />
          </div>
        </div>

      </form>
    </div>
  </div>
</div>
</div>
<?php echo $footer ?>
