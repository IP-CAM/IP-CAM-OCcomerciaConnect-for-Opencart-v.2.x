<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form" data-toggle="tooltip" title="<?php echo $button_save; ?>"
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
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_settings; ?></h3>
            </div>
            <div class="panel-body">

                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form"
                      class="form-horizontal">


                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $label_version; ?></label>
                        <div class="col-sm-10">
                            <div class="form-control">
                                <?php echo $version;?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_syncMethod; ?></label>
                        <div class="col-sm-10">
                            <?php echo \comercia\Util::html()->
                            selectbox("comerciaConnect_syncMethod",$comerciaConnect_syncMethod,$syncMethods); ?>
                        </div>
                    </div>


                    <?php foreach($stores as $store){ ?>

                    <div class="store" data-store="<?php echo $store['store_id']; ?>" data-scu="<?php echo str_replace("&amp;","&",$simple_connect_url);?>">

                    <h2 class="hide-closed">
                        <?php echo $store["name"]; ?>
                    </h2>



                    <?php if(!$store['login_success']){ ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $store["name"]; ?></label>
                        <div class="col-sm-10">
                            <input type="button"
                                   value="<?php echo $button_simple_connect; ?>"
                                   class="btn btn-success simpleConnectButton">
                        </div>
                    </div>

                    <?php } else{ ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label hide-closed">
                           <?php echo $text_actions; ?>
                        </label>

                        <label class="col-sm-2 control-label hide-opened">
                            <?php echo $store["name"]; ?>
                        </label>
                        <div class="col-sm-10">
                            <input type="button"
                                   value="<?php echo $button_simple_connect; ?>"
                                   class="btn btn-warning simpleConnectButton">
                            <a href="<?php echo $store['sync_url']; ?>" class="btn btn-success"><?php echo $button_sync; ?></a>
                            <?php if($godMode){ ?>
                            <a href="<?php echo $store['sync_url']; ?>&reset=true" class="btn btn-danger"><?php echo $button_sync_all; ?></a>
                            <?php
                   foreach($syncModels as $syncModel){
                ?>
                            <a href="<?php echo $store['sync_url']; ?>&syncModel=<?php echo $syncModel ?>"
                               class="btn btn-danger"><?php echo $button_sync." - ".$syncModel; ?></a>
                            <?php }} ?>
                            <a href="<?php echo $store['control_panel_url']; ?>"
                               class="btn btn-info"><?php echo $button_control_panel; ?></a>
                            <a type="btn btn-info" class="btn btn-info openButton hide-opened"><?php echo $button_open; ?> </a>
                            <a type="button" class="btn btn-info closeButton hide-closed"><?php echo $button_close; ?> </a>
                        </div>
                    </div>
                    <?php }?>
                    <div class="loggedIn hide-closed">
                        <div class="form-group">
                            <label class="col-sm-2 control-label"
                                   for="input-status"><?php echo $entry_status; ?></label>
                            <div class="col-sm-10">
                                <select name="<?php echo $store['store_id']; ?>_comerciaConnect_status"
                                        id="<?php echo $store['store_id']; ?>_status" class="form-control">
                                    <?php if ($store['comerciaConnect_status']) { ?>
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
                                   for="<?php echo $store['store_id']; ?>_base_url"><?php echo $entry_base_url; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_base_url"
                                       value="<?php echo $store['comerciaConnect_base_url']; ?>"
                                       id="<?php echo $store['store_id']; ?>_base_url"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"
                                   for="<?php echo $store['store_id']; ?>_auth_url"><?php echo $entry_auth_url; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="comerciaConnect_auth_url"
                                       value="<?php echo $store['comerciaConnect_auth_url']; ?>"
                                       id="<?php echo $store['store_id']; ?>_auth_url"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"
                                   for="<?php echo $store['store_id']; ?>_api_url"><?php echo $entry_api_url; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_api_url"
                                       value="<?php echo $store['comerciaConnect_api_url']; ?>"
                                       id="<?php echo $store['store_id']; ?>_api_url"
                                       class="form-control">
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-2 control-label"
                                   for="<?php echo $store['store_id']; ?>_api_key"><?php echo $entry_api_key; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="<?php echo $store['store_id']; ?>_comerciaConnect_api_key"
                                       value="<?php echo $store['comerciaConnect_api_key']; ?>"
                                       id="<?php echo $store['store_id']; ?>_api_key"
                                       class="form-control">
                            </div>
                        </div>
                    </div>
            </div>
                    <?php // end of foreach($stores as $store){
                } ?>

            </form>
        </div>
    </div>


    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_actions; ?></h3>
        </div>


        <div class="panel-body">


            <?php if($update_url){ ?>
            <a href="<?php echo $update_url; ?>" class="btn btn-success"><?php echo $button_update; ?></a>
            <?php } ?>
        </div>
    </div>
</div>
</div>

<?php echo $footer; ?>