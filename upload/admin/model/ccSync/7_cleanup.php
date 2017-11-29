<?php
use comercia\Util;
use comerciaConnect\logic\Website;

class ModelCcSync7Cleanup extends Model
{
    public function sync($data)
    {
        $website = Website::getWebsite($data->session);
    }
}