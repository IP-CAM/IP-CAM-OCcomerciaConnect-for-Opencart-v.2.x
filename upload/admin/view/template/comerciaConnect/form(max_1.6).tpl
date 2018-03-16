<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a
                href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>

    <div class="heading">
        <h1><img src="view/image/module.png" alt=""/> <?php echo $heading_title; ?></h1>
        <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a></div>
    </div>

    <div class="content">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
            <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            <table class="form">
                <tr>
                    <td><label><?php echo $label_version; ?></label></td>
                    <td> <?php echo $version;?></td>
                </tr>

                <tr>
                    <td><label><?php echo $entry_simple_connect; ?></label></td>
                    <td><a type="button" id="simple_connect" class="button"><?php echo $button_simple_connect; ?></td>
                </tr>
                <tr>
                    <td><label for="input-status"><?php echo $entry_status; ?></label></td>
                    <td>
                        <select name="comerciaConnect_status" id="input-status">
                            <?php if ($comerciaConnect_status) { ?>
                            <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                            <option value="0"><?php echo $text_disabled; ?></option>
                            <?php } else { ?>
                            <option value="1"><?php echo $text_enabled; ?></option>
                            <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="base_url"><?php echo $entry_base_url; ?></label></td>
                    <td><input type="text" name="comerciaConnect_base_url"
                               value="<?php echo $comerciaConnect_base_url; ?>" id="base_url"></td>
                </tr>

                <tr>
                    <td><label for="auth_url"><?php echo $entry_auth_url; ?></label></td>
                    <td><input type="text" name="comerciaConnect_auth_url"
                               value="<?php echo $comerciaConnect_auth_url; ?>" id="auth_url"></td>
                </tr>

                <tr>
                    <td><label for="api_url"><?php echo $entry_api_url; ?></label></td>
                    <td><input type="text" name="comerciaConnect_api_url"
                               value="<?php echo $comerciaConnect_api_url; ?>" id="api_url" class="form-control"></td>
                </tr>

                <tr>
                    <td><label for="api_key"><?php echo $entry_api_key; ?></label></td>
                    <td><input type="text" name="comerciaConnect_api_key"
                               value="<?php echo $comerciaConnect_api_key; ?>" id="api_key"></td>
                </tr>
                <tr>
                    <td><label><?php echo $text_actions; ?></label></td>
                    <td>
                <?php if($login_success){ ?>


                        <a href="<?php echo $sync_url; ?>" class="button"><?php echo $button_sync; ?></a>
                        <?php if($godMode){ ?>
                        <a href="<?php echo $sync_url; ?>&reset=true" class="button"><?php echo $button_sync_all; ?></a>
                        <?php
                   foreach($syncModels as $syncModel){
                ?>
                        <a href="<?php echo $sync_url; ?>&syncModel=<?php echo $syncModel ?>" class="button"><?php echo $button_sync." - ".$syncModel; ?></a>
                        <?php }} ?>
                        <a href="<?php echo $control_panel_url; ?>" class="button"><?php echo $button_control_panel; ?></a>
                    </td>
                <?php } ?>


                    <?php if($update_url){ ?>
                    <a href="<?php echo $update_url; ?>" class="button"><?php echo $button_update; ?></a>
                    <?php } ?>
                    </td>
                </tr>
            </table>
        </form>

    </div>
</div>




<script>
    $("#simple_connect").click(function () {
        var w = 800;
        var h = 500;
        var left = (screen.width - w) / 2;
        var top = (screen.width - h) / 2;

        window.open("<?php echo str_replace("&amp;","&",$simple_connect_url);?>", "_blank", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, copyhistory=no, width=" + w + ", height=" + h + ", top=" + top + ", left=" + left);
    });
    function simple_connect_finish(base_url, auth_url, api_url, api_key) {
        $("#base_url").val(base_url)
        $("#auth_url").val(auth_url)
        $("#api_url").val(api_url);
        $("#api_key").val(api_key);
    }
</script>
<?php echo $footer; ?>