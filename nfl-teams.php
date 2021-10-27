<?php
/**
 * Plugin Name:     NFL Teams
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     A plugin to display NFL teams for ACME Sports
 * Author:          Alif Momin
 * Author URI:      YOUR SITE HERE
 * Text Domain:     nfl-teams
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Nfl_Teams
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'nfl-data.php';

define( 'NFL_VERSION', '0.1.0' );

add_action( 'init', 'nfl_init' );

/**
 * Enqueuing styles.
 */
function nfl_scripts_and_styles() {
	wp_register_style( 'nfl-style-base', plugins_url( 'css/base.css', __FILE__ ), array(), NFL_VERSION );
	wp_enqueue_style( 'nfl-style-base' );
	wp_register_style( 'nfl-style', plugins_url( 'css/main.css', __FILE__ ), array( 'nfl-style-base' ), NFL_VERSION );
	wp_enqueue_style( 'nfl-style' );

	wp_register_script( 'nfl-script', plugins_url( 'js/main.js', __FILE__ ), array(), NFL_VERSION, true );
	wp_enqueue_script( 'nfl-script' );
}
add_action( 'wp_enqueue_scripts', 'nfl_scripts_and_styles', 20 );

// register_activation_hook( __FILE__, 'nfl_activated' );
// register_deactivation_hook( __FILE__, 'nfl_deactivated' );
// register_uninstall_hook( __FILE__, 'nfl_uninstalled' );

/**
 * Adding a hook into init.
 */
function nfl_init() {
	add_shortcode( 'nfl-teams', 'nfl_shortcode' );
}

/**
 * Function to run on plugin activation
 */
function nfl_activated() {

}

/**
 * Function to run on plugin deactivation
 */
function nfl_deactivated() {

}

/**
 * Function to run on plugin uninstall
 */
function nfl_uninstalled() {
	remove_shortcode( 'nfl-teams' );
}

/**
 * Shortcode function
 *
 * @param Attributes $atts Shortcode attributes.
 */
function nfl_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'style' => 'table',
		),
		$atts,
		'nfl-teams'
	);

	$url = 'http://delivery.chalk247.com/team_list/NFL.JSON?api_key=74db8efa2a6db279393b433d97c2bc843f8e32b0';

	$response = wp_remote_get( $url, array( 'method' => 'GET' ) );

	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
		return '<p>Something went wrong: ' . $error . '</p>';
	}

	// Retrieve status code from the response.
	if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return '<p>' . wp_remote_retrieve_response_message( $response ) . '</p>';
	}

	$response_body = wp_remote_retrieve_body( $response );
	$response_body = json_decode( $response_body );

	$response_results = $response_body->results;

	$columns = $response_results->columns;
	$teams   = $response_results->data->team;

	$html_response = '';

	// Wrapping the entire output with 'nfl-teams' class.
	$html_response .= '<div class="nfl-teams">';

	// Start buffering output.
	ob_start();
	/**
	 * Show tabs to switch output
	 */
	?>
	<div>
		<ul class="nfl-tabs">
			<li>
				<a href="#" data-target="#nfl-division" class="active">Division</a>
			</li>
			<li>
				<a href="#" data-target="#nfl-conference">Conference</a>
			</li>
			<li>
				<a href="#" data-target="#nfl-table-container">Table</a>
			</li>
		</ul>
	</div>
	<?php

	// Get the content of the buffer in $html_response variable.
	$html_response .= ob_get_clean();

	/**
	 * Sorting the teams by Conference and Division
	 */
	$html_response .= sort_teams_by_conference_divisions( $teams );

	/**
	 * Sorting the teams by Conference
	 */
	$html_response .= sort_teams_by_conference( $teams );

	/**
	 * Display all teams in a table
	 */
	$html_response .= display_teams_table( $teams, $columns );

	$html_response .= '</div>'; // End of .nfl-teams.

	return $html_response;
}

function sort_teams_by_conference_divisions( $teams ) {
	$division_array = array();
	foreach ( $teams as $team ) {
		$division_array[ $team->conference ][ $team->division ][] = $team;
	}

	$html_response  = '';
	$html_response .= '<div class="team-container" id="nfl-division">';
	foreach ( $division_array as $conference => $divisions ) {
		foreach ( $divisions as $division => $teams ) {
			$html_response .= '<h2 class="team-title">' . $conference . ' ' . $division . '</h2>';
			$html_response .= '<div class="team-grid">';
			foreach ( $teams as $team ) {
				$team_name      = strtolower( str_replace( ' ', '-', $team->display_name . '-' . $team->nickname ) );
				$html_response .= '<div class="team-info" style="background-image: url(' . plugins_url( 'img/logos-bg/' . $team_name . '-bg.webp', __FILE__ ) . ')">';
				$html_response .= '<img class="team-logo" src="' . plugins_url( 'img/logos/' . $team_name . '.png', __FILE__ ) . '" alt="" width="500" height="500" />';
				$html_response .= '<div class="team-name-container">';
				$html_response .= '<h3 class="team-name">' . $team->display_name . ' ' . $team->nickname . '</h3>';
				$html_response .= '<a href="https://www.nfl.com/teams/' . $team_name . '/" class="team-profile-link" target="blank">View Profile</a>';
				$html_response .= '</div>';
				$html_response .= '</div>';
			}
			$html_response .= '</div>';
		}
	}
	$html_response .= '</div>';
	return $html_response;
}


function sort_teams_by_conference( $teams ) {
	$nfc_teams = array();
	$afc_teams = array();
	foreach ( $teams as $team ) {
		if ( $team->conference === 'National Football Conference' ) {
			array_push( $nfc_teams, $team );
		} elseif ( $team->conference === 'American Football Conference' ) {
			array_push( $afc_teams, $team );
		}
	}

	$html_response = '';

	$html_response .= '<div id="nfl-conference" class="team-container" style="display: none">';
	// $html_response .= '<div class="team-container">';
	$html_response .= '<h2 class="team-title">NFC Teams</h2>';
	$html_response .= '<div class="team-grid">';
	foreach ( $nfc_teams as $nfc_team ) {
		$team_name      = strtolower( str_replace( ' ', '-', $nfc_team->display_name . '-' . $nfc_team->nickname ) );
		$html_response .= '<div class="team-info" style="background-image: url(' . plugins_url( 'img/logos-bg/' . $team_name . '-bg.webp', __FILE__ ) . ')">';
		$html_response .= '<img class="team-logo" src="' . plugins_url( 'img/logos/' . $team_name . '.png', __FILE__ ) . '" alt="" width="500" height="500" />';
		$html_response .= '<div class="team-name-container">';
		$html_response .= '<h3 class="team-name">' . $nfc_team->display_name . ' ' . $nfc_team->nickname . '</h3>';
		$html_response .= '<a href="https://www.nfl.com/teams/' . $team_name . '/" class="team-profile-link" target="blank">View Profile</a>';
		$html_response .= '</div>';
		$html_response .= '</div>';
	}
	$html_response .= '</div>';
	// $html_response .= '</div>';

	// $html_response .= '<div class="team-container">';
	$html_response .= '<h2 class="team-title">AFC Teams</h2>';
	$html_response .= '<div class="team-grid">';
	foreach ( $afc_teams as $afc_team ) {
		$team_name      = strtolower( str_replace( ' ', '-', $afc_team->display_name . '-' . $afc_team->nickname ) );
		$html_response .= '<div class="team-info" style="background-image: url(' . plugins_url( 'img/logos-bg/' . $team_name . '-bg.webp', __FILE__ ) . ')">';
		$html_response .= '<img class="team-logo" src="' . plugins_url( 'img/logos/' . $team_name . '.png', __FILE__ ) . '" alt="" width="500" height="500" />';
		$html_response .= '<div class="team-name-container">';
		$html_response .= '<h3 class="team-name">' . $afc_team->display_name . ' ' . $afc_team->nickname . '</h3>';
		$html_response .= '<a href="https://www.nfl.com/teams/' . $team_name . '/" class="team-profile-link" target="blank">View Profile</a>';
		$html_response .= '</div>';
		$html_response .= '</div>';
	}
	// $html_response .= '</div>';
	$html_response .= '</div>';
	$html_response .= '</div>';
	return $html_response;
}

function display_teams_table( $teams, $columns ) {
	$html_response  = '';
	$html_response .= '<div id="nfl-table-container" class="team-container" style="display: none">';
	$html_response .= '<table class="nfl-table">';
	$html_response .= '<thead>';
	$html_response .= '<tr>';
	$html_response .= '<th>';
	$html_response .= ucwords( $columns->id );
	$html_response .= '</th>';
	$html_response .= '<th>';
	$html_response .= ucwords( $columns->name );
	$html_response .= '</th>';
	$html_response .= '<th>';
	$html_response .= ucwords( $columns->display_name );
	$html_response .= '</th>';
	$html_response .= '<th>';
	$html_response .= ucwords( $columns->nickname );
	$html_response .= '</th>';
	$html_response .= '<th>';
	$html_response .= ucwords( $columns->conference );
	$html_response .= '</th>';
	$html_response .= '<th>';
	$html_response .= ucwords( $columns->division );
	$html_response .= '</th>';
	$html_response .= '</tr>';
	$html_response .= '</thead>';
	$html_response .= '<tbody>';

	foreach ( $teams as $team ) {

		$html_response .= '<tr>';
		$html_response .= '<td>' . $team->id . '</td>';
		$html_response .= '<td>' . $team->name . '</td>';
		$html_response .= '<td>' . $team->display_name . '</td>';
		$html_response .= '<td>' . $team->nickname . '</td>';
		$html_response .= '<td>' . $team->conference . '</td>';
		$html_response .= '<td>' . $team->division . '</td>';
		$html_response .= '</tr>';
	}

	$html_response .= '</tbody>';
	$html_response .= '</table>';
	$html_response .= '</div>';
	return $html_response;
}
