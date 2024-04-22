<?php

/**
 * Plugin Name: MM Manual Approve
 * Plugin URI: https://budiharyono.id/
 * Description: Manual approve user registration
 * Version: 1.0
 */

defined('ABSPATH') or die('No script kiddies please!');

function mm_user_reg_approval($user_id)
{
    update_user_meta($user_id, 'mm_approval_status', 'pending');
}

add_action('user_register', 'mm_user_reg_approval');

function mm_prevent_login($user, $username, $password)
{
    if ($user instanceof WP_User) {
        $approval_status = get_user_meta($user->ID, 'mm_approval_status', true);
        if ('pending' === $approval_status) {
            return new WP_Error('approval_pending', 'Akun Anda sedang menunggu persetujuan admin.');
        }
    }
    return $user;
}

add_filter('authenticate', 'mm_prevent_login', 30, 3);

function mm_add_approve_column($column)
{
    $column['approve_user'] = 'Approval';
    return $column;
}

add_filter('manage_users_columns', 'mm_add_approve_column');

function mm_approve_user_column_content($value, $column_name, $user_id)
{
    if ('approve_user' === $column_name) {
        $approval_status = get_user_meta($user_id, 'mm_approval_status', true);
        if ('pending' === $approval_status) {
            $approve_url = add_query_arg(array(
                'action' => 'mm_approve_user',
                'user_id' => $user_id,
                '_wpnonce' => wp_create_nonce('mm_approve_user')
            ), admin_url('users.php'));

            return '<a href="' . esc_url($approve_url) . '">Approve</a>';
        }
        return 'Approved';
    }
    return $value;
}

add_filter('manage_users_custom_column', 'mm_approve_user_column_content', 10, 3);

function mm_approve_user()
{
    if (isset($_GET['action']) && $_GET['action'] == 'mm_approve_user') {
        if (isset($_GET['user_id']) && current_user_can('edit_users')) {
            $user_id = intval($_GET['user_id']);
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mm_approve_user')) {
                $approval_status = get_user_meta($user_id, 'mm_approval_status', true);
                if ('pending' === $approval_status) {
                    delete_user_meta($user_id, 'mm_approval_status');
                    wp_update_user(array('ID' => $user_id, 'role' => 'subscriber'));
                    wp_redirect(admin_url('users.php'));
                    exit;
                }
            }
        }
    }
}

add_action('admin_init', 'mm_approve_user');
