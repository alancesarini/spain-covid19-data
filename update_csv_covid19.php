<?php


/**
 * PHP script to convert the CSV file with data about COVID19 in Spain to JSON format
 * Data taken from https://lnkd.in/gDVGKyQ
 * 
 * @author Alan Cesarini
 * 
 * @param string json_path (full path to the JSON file that will hold the JSON data)
 * @param int type_of_conversion (1 --> group data as in the CSV, 2 --> group data by date, 3 --> group data by region )
 * 
 */

// URL to CSV file
define( 'CSV_URL', 'https://lnkd.in/gDVGKyQ' );

// Path to the JSON file
$json_path = isset( $argv[1] ) ? $argv[1] : null;

if( null === $json_path ) {
	die( "ERROR: You must provide a full path for the JSON file!\n" );
}

// Type of conversion
$type_of_conversion = isset( $argv[2] ) ? $argv[2] : null;

// If the type of conversion is not 1, 2 or 3, die
if( '1' !== $type_of_conversion && '2' !== $type_of_conversion && '3' !== $type_of_conversion ) {
	die("ERROR: type of conversion parameter incorrect or missing!\n");
}

// Open the CSV file
$data = file_get_contents( CSV_URL );
//$handle = fopen( $csv_path, 'r' );

// If there's an error reading the CSV file, die
//if( false === $handle ) {
if( false === $data ) {
	die("ERROR: can't read the CSV file!\n");
}

// Read the CSV file line by line
// while ( ( $data = fgetcsv( $handle, 10000, ',' ) ) !== false ) {
// 	$array_data[] = $data;
// }
$array_data = explode( "\n", $data );

// If the data array is empty, die
if( 0 === count( $array_data ) ) {
	die( "ERROR: the CSV file is empty!\n" );
}

// Instantiate the conversion class
$csv_to_json = new CSV_To_JSON( $array_data, $json_path );

// Do the conversion
$csv_to_json->do( $type_of_conversion );





/**
 * Class that handles the CSV to JSON conversion
 */
class CSV_To_JSON {

	// Array with all the data
	private $data;

	// Array with all the ISO codes and names of the spanish regions
	private $regions;

	/**
	 * Class constructor
	 * 
	 * @param array $array_data
	 * @param string $json_path
	 */
	function __construct( $array_data, $json_path ) {

		$this->regions = array(
			"AN" =>	"Andalucía",
			"AR" =>	"Aragón",
			"AS" =>	"Asturias, Principado de",
			"CE" => "Ceuta",
			"CN" =>	"Canarias",
			"CB" =>	"Cantabria",
			"CM" =>	"Castilla-La Mancha",
			"CL" =>	"Castilla y León",
			"CT" =>	"Cataluña",
			"EX" =>	"Extremadura",
			"GA" =>	"Galicia",
			"IB" => "Islas Baleares",
			"RI" =>	"La Rioja",
			"MD" =>	"Madrid, Comunidad de",
			"ME" => "Melilla",
			"MC" =>	"Murcia, Región de",
			"NC" =>	"Navarra, Comunidad Foral de",
			"PV" =>	"País Vasco",
			"VC" =>	"Comunidad Valenciana",	
		);

		$this->data = $array_data;
		$this->json_path = $json_path;

	}

	/**
	 * Converts the CSV data to JSON
	 * 
	 * @param int $type
	 */
	function do( $type ) {

		// Skip the first and last line
		$first_line = 1;
		$last_line = count( $this->data ) - 3;

		// Array to hold the data grouped by region
		$data_region = array();

		// Array to hold the data grouped by date
		$data_date = array();

		// Array to hold the data is in the CSV
		$data_csv = array();

		// Process each line
		for( $i = $first_line; $i < $last_line; $i++ ) {
			$line = explode( ',', $this->data[ $i ] );
			if( 7 == count( $line ) ) {
				$iso = $line[0];
				$date = $line[1];
				$cases = $line[2];
				$hospitalized = $line[3];
				$icu = $line[4];
				$deceased = $line[5];
				$recuperated = str_replace( "\r", "", $line[6] );

				$data = array(
					'iso' => $iso,
					'name' => $this->regions[ $iso ],
					'date' => $date,
					'cases' => intval( $cases ) > 0 ? $cases : "0",
					'hospitalized' => intval( $hospitalized ) > 0 ? $hospitalized : "0",
					'icu' => intval( $icu ) > 0 ? $icu : "0",
					'deceased' => intval( $deceased ) > 0 ? $deceased : "0",
					'recuperated' => intval( $recuperated ) > 0 ? $recuperated : "0",
				);

				$data_csv[] = $data;

				$data2 = $data;
				unset( $data2['date'] );
				if( !isset( $data_date[ $date ] ) ) {
					$data_date[ $date ] = array( $data2 );
				} else {
					$data_date[ $date ][] = $data2;
				}

				$data3 = $data;
				unset( $data3['iso'] );					
				if( !isset( $data_region[ $iso ] ) ) {
					$data_region[ $iso ] = array( $data3 );
				} else {
					$data_region[ $iso ][] = $data3;
				}
			}
		}

		switch( $type ) {
			case '1':
				$data_to_write = $data_csv;
				break;
			case '2':
				$data_to_write = $data_date;
				break;
			case '3':
				$data_to_write = $data_region;
		}

		$result = file_put_contents( $this->json_path, json_encode( $data_to_write, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		
		if( false === $result ) {
			die( "ERROR: Can't write to file!\n" );
		}
	}

}