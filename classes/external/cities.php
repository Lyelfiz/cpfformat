<?php
namespace local_cpfformat\external;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../lib.php');

class cities extends \external_api {

    public static function execute_parameters() {
        return new \external_function_parameters([
            'query' => new \external_value(PARAM_TEXT, 'Search query for cities'),
        ]);
    }

    public static function execute($query) {
        $query = self::normalize($query);

        $cities = get_brazilian_cities();

        $results = [];
        $limit = 20; // Limit results to 20 for better performance

        foreach ($cities as $name) {
            if (stripos(self::normalize($name), $query) !== false) {
                $results [] = [
                    'value' => $name,
                    'label' => $name
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    public static function execute_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'value' => new \external_value(PARAM_TEXT, 'Value'),
                'label' => new \external_value(PARAM_TEXT, 'Label'),
            ])
        );
    }

    private static function normalize($string) {
        $string = mb_strlower($string, 'UTF-8');
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        return preg_replace('/[^a-z0-9 ]/', '', $string);
    }
}