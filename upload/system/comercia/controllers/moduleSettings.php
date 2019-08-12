<?php

namespace comercia\controllers;

use comercia\Util;

class ModuleSettings
{
    var $fields = array();
    var $prepare;
    var $postFinish;
    var $name;
    var $baseVersion;

    function __construct($name, $baseVersion = "2.2")
    {
        $this->setName($name);
        $this->setBaseVersion($baseVersion);
        $this->prepare = function () {
        };
        $this->postFinish = function () {
        };
    }


    function setBaseVersion($version)
    {
        $this->baseVersion = $version;
        return $this;
    }

    function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    function setFields($first)
    {
        if (!is_array($first)) {
            $first = func_get_args();
        }
        $this->fields = $first;
        return $this;
    }

    function prepare($func)
    {
        $this->prepare = $func;
        return $this;
    }


    function postFinish($func)
    {
        $this->postFinish = $func;
        return $this;
    }

    function run($forceRedirect = false)
    {
        //load the language data
        $data = array();
        $name = $this->name;
        $form = Util::form($data);
        if (version_compare($this->baseVersion, "2.3", ">=")) {
            Util::load()->language("extension/module/" . $name, $data);
        } else {
            Util::load()->language("module/" . $name, $data);
        }


        if ($forceRedirect) {
            $data['redirect'] = $forceRedirect;
        }

        $form->finish(function ($data) {
            Util::config()->set($this->name, Util::request()->post()->all());
            Util::session()->success = $data['msg_settings_saved'];
            $postFinish = $this->postFinish;
            if (is_callable($postFinish)) {
                $avoid_redirect = $postFinish($data);
            }
            if (!$avoid_redirect) {
                Util::response()->redirect(@$data['redirect'] ?: Util::route()->extension());
            }
        });

        //handle the form when finished
        $formFields = $this->fields;
        $prepare = $this->prepare;
        if (is_callable($prepare)) {
            $prepare($data);
        }

        //place the prepared data into the form
        $form
            ->fillFromSessionClear("error_warning", "success")
            ->fillFromPost($formFields)
            ->fillFromConfig($formFields);

        Util::breadcrumb($data)
            ->add("text_home", "common/home")
            ->add("settings_title", Util::route()->extension());


        //handle document related things
        Util::document()->setTitle(Util::language()->heading_title);

        //create links
        if (version_compare($this->baseVersion, "2.3", ">=")) {
            $data['action'] =  Util::url()->link('extension/module/' . $name);
        }else{
            $data['action'] =  Util::url()->link('module/' . $name);
        }

        $data['action'] = Util::url()->link(Util::route()->extension($name));
        $data['cancel'] = Util::url()->link(Util::route()->extension());

        //create a response
        if (version_compare($this->baseVersion, "2.3", ">=")) {
            Util::response()->view("extension/module/" . $name, $data);
        } else {
            Util::response()->view("module/" . $name , $data);
        }
    }

}

?>