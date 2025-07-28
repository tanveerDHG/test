<?php

class WPF_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing sales form submissions
        $table_name = $wpdb->prefix . 'wpf_sales_forms';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id varchar(50) NOT NULL,
            salesperson_name varchar(100) NOT NULL,
            salesperson_email varchar(100) NOT NULL,
            client_name varchar(100) NOT NULL,
            client_email varchar(100) NOT NULL,
            client_phone varchar(20),
            service_description text NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            payment_link varchar(255),
            payment_status varchar(20) DEFAULT 'pending',
            conditions_accepted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY form_id (form_id)
        ) $charset_collate;";
        
        // Table for storing payment transactions
        $transactions_table = $wpdb->prefix . 'wpf_transactions';
        
        $sql2 = "CREATE TABLE $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id varchar(50) NOT NULL,
            transaction_id varchar(100),
            payment_method varchar(50),
            amount_paid decimal(10,2),
            payment_date datetime,
            status varchar(20) DEFAULT 'pending',
            gateway_response text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
    }
    
    public static function insert_sales_form($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpf_sales_forms';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'form_id' => $data['form_id'],
                'salesperson_name' => $data['salesperson_name'],
                'salesperson_email' => $data['salesperson_email'],
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_phone' => $data['client_phone'],
                'service_description' => $data['service_description'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'conditions_accepted' => $data['conditions_accepted']
            ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%d'
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public static function update_payment_link($form_id, $payment_link) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpf_sales_forms';
        
        return $wpdb->update(
            $table_name,
            array('payment_link' => $payment_link),
            array('form_id' => $form_id),
            array('%s'),
            array('%s')
        );
    }
    
    public static function get_form_by_id($form_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpf_sales_forms';
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE form_id = %s", $form_id)
        );
    }
    
    public static function update_payment_status($form_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpf_sales_forms';
        
        return $wpdb->update(
            $table_name,
            array('payment_status' => $status),
            array('form_id' => $form_id),
            array('%s'),
            array('%s')
        );
    }
    
    public static function insert_transaction($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpf_transactions';
        
        return $wpdb->insert(
            $table_name,
            array(
                'form_id' => $data['form_id'],
                'transaction_id' => $data['transaction_id'],
                'payment_method' => $data['payment_method'],
                'amount_paid' => $data['amount_paid'],
                'payment_date' => $data['payment_date'],
                'status' => $data['status'],
                'gateway_response' => $data['gateway_response']
            ),
            array(
                '%s', '%s', '%s', '%f', '%s', '%s', '%s'
            )
        );
    }
}