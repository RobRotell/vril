<?php


namespace Cine\Controllers;


use WP_Application_Passwords;
use WP_Error;
use WP_User;


defined( 'ABSPATH' ) || exit;


final class Auth_Tokens extends \Vril\Core_Classes\Auth_Tokens
{
	protected string $app_name = 'cine_v2';
	
}
