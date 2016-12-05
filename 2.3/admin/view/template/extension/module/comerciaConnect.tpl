<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-account" data-toggle="tooltip" title="<?php echo $button_save; ?>"
                        class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>"
                   class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">

                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-account"
                      class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-status"><?php echo $entry_simple_connect; ?></label>
                        <div class="col-sm-10">
                            <input type="button" id="simple_connect"
                                   value="<?php echo $button_simple_connect; ?>" class="btn btn-success">
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="comerciaConnect_status" id="input-status" class="form-control">
                                <?php if ($comerciaConnect_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-status"><?php echo $entry_auth_url; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="comerciaConnect_auth_url"
                                   value="<?php echo $comerciaConnect_auth_url; ?>" id="auth_url" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-status"><?php echo $entry_api_url; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="comerciaConnect_api_url"
                                   value="<?php echo $comerciaConnect_api_url; ?>" id="api_url" class="form-control">
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-status"><?php echo $entry_api_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="comerciaConnect_api_key"
                                   value="<?php echo $comerciaConnect_api_key; ?>" id="api_key" class="form-control">
                        </div>
                    </div>

                </form>
            </div>
        </div>
<?php if($login_success){ ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_actions; ?></h3>
            </div>
            <div class="panel-body">
                <a href="<?php echo $sync_url; ?>" class="btn btn-info"><?php echo $button_sync; ?></a>
                <a href="<?php echo $control_panel_url; ?>" class="btn btn-info"><?php echo $button_control_panel; ?></a>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<script>
    $("#simple_connect").click(function(){
        var w=800;
        var h=500;
        var left = (screen.width/2)-(w/2);
        var top = (screen.height/2)-(h/2);

          window.open("<?php echo str_replace("&amp;","&",$simple_connect_url);?>", "_blank", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, copyhistory=no, width="+w+", height="+h+", top="+top+", left="+left);
    });
    function simple_connect_finish(auth_url,api_url,api_key){
        $("#auth_url").val(auth_url)
        $("#api_url").val(api_url);
        $("#api_key").val(api_key);
    }
</script>
<?php echo $footer; ?>