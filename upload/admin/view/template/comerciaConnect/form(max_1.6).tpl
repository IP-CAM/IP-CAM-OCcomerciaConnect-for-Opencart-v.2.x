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
                <?php foreach($stores as $store){ ?>
                <tbody class="store" data-store="<?php echo $store['store_id']; ?>" data-scu="<?php echo str_replace("
                       &amp;
                "," & ",$simple_connect_url);?>">

                <tr>
                    <td class="store-title hide-closed" colspan="2"><?php echo $store["name"]; ?></td>
                </tr>

                <?php if(!$store['login_success']){ ?>
                <tr>
                    <td><label><?php echo $entry_simple_connect; ?></label></td>
                    <td>
                        <a type="button" class="button simpleConnectButton"><?php echo $button_simple_connect; ?>
                    </td>
                </tr>
                <?php } if($store['login_success']){ ?>
                <tr>
                    <td>
                        <span class="hide-closed"><?php echo $text_actions; ?></span>
                        <span class="hide-opened"><?php echo $store["name"]; ?></span>

                    </td>
                    <td>

                        <a href="<?php echo $store['sync_url']; ?>" class="button"><?php echo $button_sync; ?></a>
                        <?php if($godMode){ ?>
                        <a href="<?php echo $store['sync_url']; ?>&reset=true"
                           class="button"><?php echo $button_sync_all; ?></a>
                        <?php foreach($syncModels as $syncModel){ ?>
                        <a href="<?php echo $store['sync_url']; ?>&syncModel=<?php echo $syncModel ?>"
                           class="button"><?php echo $button_sync." - ".$syncModel; ?></a>
                        <?php }} ?>
                        <a href="<?php echo $store['control_panel_url']; ?>"
                           class="button"><?php echo $button_control_panel; ?></a>
                        <a type="button" class="button openButton hide-opened"><?php echo $button_open; ?>
                        <a type="button" class="button closeButton hide-closed"><?php echo $button_close; ?>
                    </td>
                </tr>
                <?php } ?>
                <tr class="hide-closed">
                    <td><label><?php echo $entry_simple_connect; ?></label></td>
                    <td>
                        <a type="button" class="button simpleConnectButton"><?php echo $button_simple_connect; ?>
                    </td>
                </tr>
                <tr class="hide-closed">
                    <td><label for="input-status"><?php echo $entry_status; ?></label></td>
                    <td>
                        <select name="<?php echo $store['store_id']; ?>_comerciaConnect_status" id="input-status">
                            <?php if ($store['comerciaConnect_status']) { ?>
                            <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                            <option value="0"><?php echo $text_disabled; ?></option>
                            <?php } else { ?>
                            <option value="1"><?php echo $text_enabled; ?></option>
                            <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr class="hide-closed">
                    <td><label for="<?php echo $store['store_id']; ?>_base_url"><?php echo $entry_base_url; ?></label>
                    </td>
                    <td><input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_base_url"
                               value="<?php echo $store['comerciaConnect_base_url']; ?>"
                               id="<?php echo $store['store_id']; ?>_base_url"></td>
                </tr>

                <tr class="hide-closed">
                    <td><label for="<?php echo $store['store_id']; ?>_auth_url"><?php echo $entry_auth_url; ?></label>
                    </td>
                    <td><input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_auth_url"
                               value="<?php echo $store['comerciaConnect_auth_url']; ?>"
                               id="<?php echo $store['store_id']; ?>_auth_url"></td>
                </tr>

                <tr class="hide-closed">
                    <td><label for="<?php echo $store['store_id']; ?>_api_url"><?php echo $entry_api_url; ?></label>
                    </td>
                    <td><input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_api_url"
                               value="<?php echo $store['comerciaConnect_api_url']; ?>"
                               id="<?php echo $store['store_id']; ?>_api_url" class="form-control"></td>
                </tr>

                <tr class="hide-closed">
                    <td><label for="<?php echo $store['store_id']; ?>_api_key"><?php echo $entry_api_key; ?></label>
                    </td>
                    <td><input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_api_key"
                               value="<?php echo $store['comerciaConnect_api_key']; ?>"
                               id="<?php echo $store['store_id']; ?>_api_key"></td>
                </tr>
                </tbody>
                <?php // end of foreach($stores as $store){
                } ?>
            </table>
            <?php if($update_url){ ?>
            <a href="<?php echo $update_url; ?>" class="button"><?php echo $button_update; ?></a>
            <?php } ?>
        </form>

    </div>
</div>

<?php echo $footer; ?>