<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/codecandies/reg-user-dashboard
 * @since             0.1
 * @package           Reg_User_Dashboard
 *
 * @wordpress-plugin
 * Plugin Name:       Recent registered user dashboard
 * Plugin URI:        https://github.com/codecandies/reg-user-dashboard
 * Description:       Show a count of newly registered users since a date in a dashboard widget
 * Version:           0.5
 * Author:            Nico Brünjes
 * Author URI:        http://couchblog.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       reg-user-dashboard
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Gets all widget options, or only options for a specified widget if a widget id is provided.
 *
 * @param string $widget_id Optional. If provided, will only get options for that widget.
 * @return array An associative array
 */
function get_dashboard_widget_options( $widget_id='' ) {
    //Fetch ALL dashboard widget options from the db...
    $opts = get_option( 'dashboard_widget_options' );

    //If no widget is specified, return everything
    if ( empty( $widget_id ) )
        return $opts;

    //If we request a widget and it exists, return it
    if ( isset( $opts[$widget_id] ) )
        return $opts[$widget_id];

    //Something went wrong...
    return false;
}

/**
 * Saves an array of options for a single dashboard widget to the database.
 * Can also be used to define default values for a widget.
 *
 * @param string $widget_id The name of the widget being updated
 * @param array $args An associative array of options being saved.
 * @param bool $add_only Set to true if you don't want to override any existing options.
 */
function update_dashboard_widget_options( $widget_id , $args=array(), $add_only=false ) {
    //Fetch ALL dashboard widget options from the db...
    $opts = get_option( 'dashboard_widget_options' );

    //Get just our widget's options, or set empty array
    $w_opts = ( isset( $opts[$widget_id] ) ) ? $opts[$widget_id] : array();

    if ( $add_only ) {
        //Flesh out any missing options (existing ones overwrite new ones)
        $opts[$widget_id] = array_merge($args,$w_opts);
    }
    else {
        //Merge new options with existing ones, and add it back to the widgets array
        $opts[$widget_id] = array_merge($w_opts,$args);
    }

    //Save the entire widgets array back to the db
    return update_option('dashboard_widget_options', $opts);
}

/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function reg_user_add_dashboard_widget() {

	wp_add_dashboard_widget(
		'reg-user-dashboard',
		'Neu registrierte Nutzer',
		'reg_user_dashboard_widget_function',
		'reg_user_dashboard_control_function'
    );	
}
add_action( 'wp_dashboard_setup', 'reg_user_add_dashboard_widget' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function reg_user_dashboard_widget_function() {
	// Display whatever it is you want to show.
	?>
<style>
	div.bignumber__container {
		background: #eee;
		border: 1px solid #ccc;
		margin: 5px;
		padding: 20px;
		text-align: center;
	}
	div.bignumber__number {
		color: #666;
		font-size: 52px;
		font-weight: 700;
	}
	#reg-user-dashboard .edit-box {
		opacity: 1;
	}
</style>
	<?php
	$id = "reg_user_dashboard_widget";
	$today = date('Y-m-d');
	$options = get_dashboard_widget_options( $id );
	$startdate = $options['startdate'] ? $options['startdate'] : $today;
	$enddate = $options['enddate'] ? $options['enddate'] : $today;
	$usercount = 0;

	$args = [
	    'date_query' => [
	        [ 'after'  => $startdate, 'before' => $enddate, 'inclusive' => true, 'count_total' => true ],
	    ] 
	];

	$query = new WP_User_Query( $args );
	$usercount = $query->total_users > 0 ? $query->total_users : "0.0";
	$display_startdate = new DateTime($startdate);
	$display_startdate = $display_startdate->format('d.m.Y');
	$display_enddate = new DateTime($enddate);
	$display_enddate = $display_enddate->format('d.m.Y');
	echo '<div class="bignumber">';
	echo '<h3>Neue registrierte Nutzer zwischen ' . $display_startdate . ' und ' . $display_enddate . '.</h3>';
	echo '<div class="bignumber__container">';
	echo '<div class="bignumber__number">';
	echo $usercount;
	echo '</div>';
	echo '</div>';
	echo '</div>';
}

function reg_user_dashboard_control_function() {
	$id = "reg_user_dashboard_widget";

	// update widget options
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget_id']) ) {
		$startdate = $_POST['startdate'];
		$enddate = $_POST['enddate'];
		update_dashboard_widget_options( $id , array('startdate' => $startdate, 'enddate' => $enddate), $add_only=false );
	}

	$options = get_dashboard_widget_options( $id );
	$today = date('Y-m-d');
	$startdate = $options['startdate'] ? $options['startdate'] : $today;
	$enddate = $options['enddate'] ? $options['enddate'] : $today;
	?>
	<style>
		.edit-box {
			opacity: 1 !important;
		}
		div.bignumber__container {
			margin: 0 0 10px;
		}
		div.bignumber__number {
			color: #666;
			font-size: 52px;
			font-weight: 700;
		}
		div.bignumber__formfield {
			margin-bottom: 10px;
			overflow: hidden;
		}
		div.bignumber__formline {
			margin-bottom: 10px;
		}
		div.bignumber__formfield > .bignumber__formline {
			float: left;
			margin-left: 20px;
			margin-bottom: 0px;
			width: 45%;
		}
		div.bignumber__formfield > .bignumber__formline:first-child {
			margin-left: 0;
		}
	</style>
	<div class="bignumber__container">
		<p>Geben Sie Start- und Enddatum für die Anzeige von neu registrierten Nutzern ein.</p>
		<div class="bignumber__formfield">
			<div class="bignumber__formline">
				<label for="startdate"><strong>Startdatum</strong></label><br>
				<input type="date" name="startdate" value="<?php echo $startdate; ?>" id="startdate" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">
			</div>
			<div class="bignumber__formline">
				<label for="enddate"><strong>Enddatum</strong></label><br>
				<input type="date" name="enddate" value="<?php echo $enddate; ?>" id="enddate" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">
			</div>
		</div>
	</div>
	<?php
}
