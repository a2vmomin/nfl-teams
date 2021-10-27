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

define( 'NFL_VERSION', '0.1.0' );

add_action( 'init', 'nfl_init' );

/**
 * Enqueuing styles and scripts.
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
function nfl_shortcode() {

	/**
	 * Step 1: Hit the API and collect the response.
	 * Step 2: Check if WordPress can hit the API without error. If there are any errors then return them.
	 * Step 3: If Step 2 did not result in error we check if the response code is 200. It it is other than 200, return the response message.
	 * Step 4: Retrieve the body of the response and decode the JSON string.
	 * Step 5: Create a variable $html_response to hold the output of loops.
	 * Step 6: Return the output so that the shortcode output can be displayed.
	 */

	// Store the URL in a variable and hit the API.
	$url      = 'http://delivery.chalk247.com/team_list/NFL.JSON?api_key=74db8efa2a6db279393b433d97c2bc843f8e32b0';
	$response = wp_remote_get( $url, array( 'method' => 'GET' ) );

	// Check if there is any errors.
	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
		return '<p>Something went wrong: ' . $error . '</p>';
	}

	// Retrieve status code from the response and return error if the status code is not 200.
	if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return '<p>' . wp_remote_retrieve_response_message( $response ) . '</p>';
	}

	// Retrive the body of the response and decode the JSON string.
	$response_body = wp_remote_retrieve_body( $response );
	$response_body = json_decode( $response_body );

	// Get data on the 'results' key into $response_results variable.
	$response_results = $response_body->results;

	// Get column names.
	$columns = $response_results->columns;

	// Get team data.
	$teams = $response_results->data->team;

	// Initialize an empty variable to hold the response of shortcode.
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

	// Sorting the teams by Conference and Division.
	$html_response .= sort_teams_by_conference_divisions( $teams );

	// Sorting the teams by Conference.
	$html_response .= sort_teams_by_conference( $teams );

	// Display all teams in a table.
	$html_response .= display_teams_table( $teams, $columns );

	$html_response .= '</div>'; // End of .nfl-teams.

	return $html_response;
}

/**
 * Function to show the teams by conference and divisions.
 *
 * @param $teams Get the $teams variable.
 * @return string $html_response.
 */
function sort_teams_by_conference_divisions( $teams ) {

	/**
	 * Initialize an empty $division_array.
	 * Organize the associative array to have Conference as keys. The value on these conference keys will hold another associative array with divisions as the keys.
	 * The divisions keys will then hold the team data sorted by conference and divisions.
	 */
	$division_array = array();
	foreach ( $teams as $team ) {
		$division_array[ $team->conference ][ $team->division ][] = $team;
	}

	// Initialize an empty variable to hold the response.
	$html_response = '';

	// Create a wrapper div to hold the data.
	$html_response .= '<div class="team-container" id="nfl-division">';

	// Loop through the $division_array using foreach loop with key value pair. $conference will have the name of the Conference and $divisions will have the Divisions.
	foreach ( $division_array as $conference => $divisions ) {
		// Loop through the $divisions using foreach loop with key value pair. $division will have the name of the Division and $teams will have the Team array.
		foreach ( $divisions as $division => $teams ) {
			$html_response .= '<h2 class="team-title">' . $conference . ' ' . $division . '</h2>';
			$html_response .= '<div class="team-grid">';

			// Loop through the $teams array and display each team.
			foreach ( $teams as $team ) {
				// Create a $team_name variable to hold the formatted team name value that will be used to display team logo, background image and profile link.
				$team_name = strtolower( str_replace( ' ', '-', $team->display_name . '-' . $team->nickname ) );
				// Show background image.
				$html_response .= '<div class="team-info" style="background-image: url(' . plugins_url( 'img/logos-bg/' . $team_name . '-bg.webp', __FILE__ ) . ')">';
				// Show logo.
				$html_response .= '<img class="team-logo" src="' . plugins_url( 'img/logos/' . $team_name . '.png', __FILE__ ) . '" alt="" width="500" height="500" />';
				$html_response .= '<div class="team-name-container">';
				$html_response .= '<h3 class="team-name">' . $team->display_name . ' ' . $team->nickname . '</h3>';
				// Generate specific team profile link to the NFL website.
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

/**
 * Function to show the teams by conference.
 *
 * @param $teams Get the $teams variable.
 * @return string $html_response.
 */
function sort_teams_by_conference( $teams ) {

	// Initialize empty arrays. $nfc_teams -> NFC Teams. $afc_teams -> AFC Teams.
	$nfc_teams = array();
	$afc_teams = array();

	// Loop through the team data and populate $nfc_teams and $afc_teams based on conference value.
	foreach ( $teams as $team ) {
		if ( $team->conference === 'National Football Conference' ) {
			array_push( $nfc_teams, $team );
		} elseif ( $team->conference === 'American Football Conference' ) {
			array_push( $afc_teams, $team );
		}
	}

	// Initialize an empty variable to hold the response.
	$html_response = '';

	// Create a wrapper div for NFC teams and set its display attribute to none. We will control this via javascript.
	$html_response .= '<div id="nfl-conference" class="team-container" style="display: none">';

	// Output for NFC teams begin.
	$html_response .= '<h2 class="team-title">NFC Teams</h2>';
	$html_response .= '<div class="team-grid">';

	// Loop through the array and display the data.
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
	// Output for NFC teams end.

	// Output for AFC teams begin.
	$html_response .= '<h2 class="team-title">AFC Teams</h2>';
	$html_response .= '<div class="team-grid">';

	// Loop through the array and display the data.
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
	$html_response .= '</div>';
	// Output for AFC teams end.

	$html_response .= '</div>'; // End of #nfl-conference div.
	return $html_response;
}

/**
 * Function to show the teams by conference and divisions.
 *
 * @param $teams Get the teams variable.
 * @param $columns Get the columns for the table.
 * @return string $html_response.
 */
function display_teams_table( $teams, $columns ) {
	// Initialize an empty variable to hold the response.
	$html_response  = '';
	$html_response .= '<div id="nfl-table-container" class="team-container" style="display: none">';
	// Generate the table layout.
	$html_response .= '<table class="nfl-table">';
	$html_response .= '<thead>';
	// Display table headers.
	$html_response .= '<tr>';
	$html_response .= '<th class="table-id">';
	$html_response .= ucwords( $columns->id );
	$html_response .= '</th>';
	$html_response .= '<th class="table-name">';
	$html_response .= ucwords( $columns->name );
	$html_response .= '</th>';
	$html_response .= '<th class="table-display-name">';
	$html_response .= ucwords( $columns->display_name );
	$html_response .= '</th>';
	$html_response .= '<th class="table-nickname">';
	$html_response .= ucwords( $columns->nickname );
	$html_response .= '</th>';
	$html_response .= '<th class="table-conference">';
	$html_response .= ucwords( $columns->conference );
	$html_response .= '</th>';
	$html_response .= '<th class="table-division">';
	$html_response .= ucwords( $columns->division );
	$html_response .= '</th>';
	$html_response .= '</tr>';
	$html_response .= '</thead>';
	$html_response .= '<tbody>';

	// Loop through the $teams array to generate table row and display team data.
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
