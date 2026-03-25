<?php

if (! function_exists('mb_rtrim')) {
    function mb_rtrim(string $string, string $characters = " \n\r\t\v\0"): string
    {
        return rtrim($string, $characters);
    }
}

if (class_exists('CI_Model') && ! class_exists('CiModel')) {
    class_alias('CI_Model', 'CiModel');
}
if (class_exists('MX_Controller') && ! class_exists('MxController')) {
    class_alias('MX_Controller', 'MxController');
}
if (class_exists('MX_Loader') && ! class_exists('MxLoader')) {
    class_alias('MX_Loader', 'MxLoader');
}
if (class_exists('MX_Router') && ! class_exists('MxRouter')) {
    class_alias('MX_Router', 'MxRouter');
}

$legacyAliases = [
    'AdminController' => 'Admin_Controller',
    'BaseController' => 'Base_Controller',
    'GuestController' => 'Guest_Controller',
    'UserController' => 'User_Controller',
    'FormValidationModel' => 'Form_Validation_Model',
    'MyModel' => 'MY_Model',
    'ResponseModel' => 'Response_Model',
    'MyLoader' => 'MY_Loader',
    'MyRouter' => 'MY_Router',
    \Modules\CustomFields\Models\ClientCustom::class => 'Mdl_Client_Custom',
];

foreach ($legacyAliases as $modern => $legacy) {
    if (class_exists($modern) && ! class_exists($legacy)) {
        class_alias($modern, $legacy);
    }
}
