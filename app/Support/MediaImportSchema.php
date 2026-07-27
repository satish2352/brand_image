<?php

namespace App\Support;

/**
 * Single source of truth for the bulk media upload sheet.
 *
 * The sample template, the import validator, the error log and the on-screen
 * instructions are all generated from this list, so a column can never drift
 * between what we hand out and what we accept.
 *
 * key      : internal field name used after parsing
 * label    : exact header written into the sample template
 * required : row is rejected when this is blank
 * type     : text | decimal | int | lookup | list | status
 * aliases  : extra normalised headers accepted from user supplied files
 *            (the label itself is always accepted)
 * help     : shown on the Instructions sheet and the Import tab
 * samples  : two example values written into the template
 */
class MediaImportSchema
{
    public const COLUMNS = [
        [
            'key' => 'media_title',
            'label' => 'Media Title',
            // Only the Hoardings form collects a title; other categories leave it
            // blank, so it is required per-category (see categoryRules) not globally.
            'required' => false,
            'type' => 'text',
            'aliases' => ['title', 'sitename', 'sitetitle', 'mediename'],
            'help' => 'Display name of the site, e.g. "Mumbai Naka Unipole".',
            'samples' => ['Mumbai Naka Unipole', 'Airport Road Billboard'],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'category',
            'label' => 'Category',
            'required' => true,
            'type' => 'lookup',
            'aliases' => ['mediacategory', 'categoryname'],
            'help' => 'Must match a Category master name exactly (see Master Reference sheet).',
            'samples' => ['Hoardings', 'Mall Media'],
        ],
        [
            'key' => 'state',
            'label' => 'State',
            'required' => true,
            'type' => 'lookup',
            'aliases' => ['statename'],
            'help' => 'Must match a State master name.',
            'samples' => ['Maharashtra', 'Maharashtra'],
        ],
        [
            'key' => 'district',
            'label' => 'District',
            'required' => true,
            'type' => 'lookup',
            'aliases' => ['districtname'],
            'help' => 'Must belong to the State given in the same row.',
            'samples' => ['Nashik', 'Pune'],
        ],
        [
            'key' => 'city',
            'label' => 'City',
            'required' => true,
            'type' => 'lookup',
            'aliases' => ['cityname', 'town', 'townname'],
            'help' => 'Must belong to the District given in the same row.',
            'samples' => ['Nashik', 'Pune'],
        ],
        [
            'key' => 'area',
            'label' => 'Area',
            'required' => true,
            'type' => 'lookup',
            'aliases' => ['areaname', 'location', 'locality'],
            'help' => 'Must belong to the City given in the same row.',
            'samples' => ['Mumbai Naka', 'Viman Nagar'],
        ],
        [
            'key' => 'vendor_code',
            'label' => 'Vendor Code',
            'required' => true,
            'type' => 'lookup',
            'aliases' => ['vendorcd', 'ownercode'],
            'help' => 'Vendor / owner code from the Vendor master. Leave blank only if Vendor Name is filled.',
            'samples' => ['VND001', 'VND002'],
        ],
        [
            'key' => 'vendor_name',
            'label' => 'Vendor Name',
            'required' => false,
            'type' => 'lookup',
            'aliases' => ['owner', 'ownername'],
            'help' => 'Used only when Vendor Code is blank. Must match the Vendor master name.',
            'samples' => ['Brand Image Outdoor', 'Skyline Media'],
        ],
        [
            'key' => 'hoarding_code',
            'label' => 'Hoarding Code',
            'required' => false,
            'type' => 'text',
            'aliases' => ['hdcode', 'uniquecode'],
            'help' => 'Leave blank to auto generate (HD000001...). In "Update existing" mode this is the matching key.',
            'samples' => ['', ''],
            'scope' => 'full',
        ],
        [
            'key' => 'media_code',
            'label' => 'Media Code',
            'required' => false,
            'type' => 'text',
            'aliases' => ['sitecode'],
            'help' => 'Optional internal code, e.g. MSH_NSK_VND001_01. Must be unique when supplied.',
            'samples' => ['', ''],
            'scope' => 'full',
        ],
        [
            'key' => 'address',
            'label' => 'Address',
            'required' => false,
            'type' => 'text',
            'aliases' => ['fulladdress', 'siteaddress'],
            'help' => 'Full postal / descriptive address of the site.',
            'samples' => ['Near Mumbai Naka Signal, Nashik', 'Airport Road, Pune'],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'width',
            'label' => 'Width (ft)',
            'required' => true,
            'type' => 'decimal',
            'aliases' => ['width', 'w'],
            'help' => 'Numeric, greater than 0.',
            'samples' => ['40', '20'],
        ],
        [
            'key' => 'height',
            'label' => 'Height (ft)',
            'required' => true,
            'type' => 'decimal',
            'aliases' => ['height', 'h'],
            'help' => 'Numeric, greater than 0.',
            'samples' => ['20', '10'],
        ],
        [
            'key' => 'latitude',
            'label' => 'Latitude',
            'required' => true,
            'type' => 'decimal',
            'aliases' => ['lat', 'gpslatitude'],
            'help' => 'Decimal degrees between -90 and 90, e.g. 19.9975.',
            'samples' => ['19.9974533', '18.5679234'],
        ],
        [
            'key' => 'longitude',
            'label' => 'Longitude',
            'required' => true,
            'type' => 'decimal',
            'aliases' => ['lng', 'lon', 'gpslongitude'],
            'help' => 'Decimal degrees between -180 and 180, e.g. 73.7898.',
            'samples' => ['73.7898023', '73.9143210'],
        ],
        [
            'key' => 'price',
            'label' => 'Price (Monthly)',
            'required' => true,
            'type' => 'decimal',
            'aliases' => ['price', 'rate', 'monthlyprice', 'monthlyrate'],
            'help' => 'Monthly rental in rupees. Numbers only, no currency symbol or comma.',
            'samples' => ['85000', '45000'],
        ],
        [
            'key' => 'illumination',
            'label' => 'Illumination',
            'required' => false,
            'type' => 'lookup',
            'aliases' => ['illuminationname', 'lighting'],
            'help' => 'Must match an Illumination master name, e.g. "Front Lit".',
            'samples' => ['Front Lit', 'Non Lit'],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'facing',
            'label' => 'Facing',
            'required' => false,
            'type' => 'text',
            'aliases' => ['facingdirection', 'direction'],
            'help' => 'Free text, e.g. "Facing Mumbai Naka to CBS".',
            'samples' => ['Mumbai Naka to CBS', 'Airport to City'],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'area_type',
            'label' => 'Area Type',
            'required' => false,
            'type' => 'lookup',
            'aliases' => ['areatype', 'areatypename', 'zone'],
            'help' => 'Must match an Area Type master name, e.g. "Commercial".',
            'samples' => ['Commercial', 'Residential'],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'highway',
            'label' => 'Highway',
            'required' => false,
            'type' => 'lookup',
            'aliases' => ['highwayname', 'nh', 'sh'],
            'help' => 'Must match a Highway master name.',
            'samples' => ['NH-60', ''],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'landmarks',
            'label' => 'Landmarks',
            'required' => false,
            'type' => 'list',
            'aliases' => ['landmark', 'nearbylandmarks'],
            'help' => 'Comma separated Landmark master names, e.g. "Bus Stand, City Mall".',
            'samples' => ['Bus Stand, City Mall', ''],
            'categories' => ['hoardings'],
        ],
        [
            'key' => 'media_type',
            'label' => 'Media Type',
            'required' => false,
            'type' => 'text',
            'aliases' => ['mediatype'],
            'help' => 'Free text sub type, e.g. "Unipole", "Digital Screen".',
            'samples' => ['Unipole', 'Billboard'],
            'categories' => ['airport'],
        ],
        [
            'key' => 'media_format',
            'label' => 'Media Format',
            'required' => false,
            'type' => 'text',
            'aliases' => ['format'],
            'help' => 'Required by the Mall Media category, e.g. "Atrium Branding".',
            'samples' => ['', 'Atrium Branding'],
            'categories' => ['mall'],
        ],
        [
            'key' => 'mall_name',
            'label' => 'Mall Name',
            'required' => false,
            'type' => 'text',
            'aliases' => ['mall'],
            'help' => 'Required by the Mall Media category.',
            'samples' => ['', 'City Centre Mall'],
            'categories' => ['mall'],
        ],
        [
            'key' => 'airport_name',
            'label' => 'Airport Name',
            'required' => false,
            'type' => 'text',
            'aliases' => ['airport'],
            'help' => 'Required by the Airport Branding category.',
            'samples' => ['', ''],
            'categories' => ['airport'],
        ],
        [
            'key' => 'zone_type',
            'label' => 'Zone Type',
            'required' => false,
            'type' => 'text',
            'aliases' => ['zonetype'],
            'help' => 'Airport Branding only. Allowed values: Arrival, Departure.',
            'samples' => ['', ''],
            'categories' => ['airport'],
        ],
        [
            'key' => 'transit_type',
            'label' => 'Transit Type',
            'required' => false,
            'type' => 'text',
            'aliases' => ['transit'],
            'help' => 'Required by the Transit Media category, e.g. "Bus".',
            'samples' => ['', ''],
            'categories' => ['transit', 'transmit'],
        ],
        [
            'key' => 'branding_type',
            'label' => 'Branding Type',
            'required' => false,
            'type' => 'text',
            'aliases' => ['branding'],
            'help' => 'Required by the Transit Media category, e.g. "Full Wrap".',
            'samples' => ['', ''],
            'categories' => ['transit', 'transmit'],
        ],
        [
            'key' => 'vehicle_count',
            'label' => 'Vehicle Count',
            'required' => false,
            'type' => 'int',
            'aliases' => ['vehicles', 'noofvehicles'],
            'help' => 'Transit Media only. Whole number.',
            'samples' => ['', ''],
            'categories' => ['transit', 'transmit'],
        ],
        [
            'key' => 'building_name',
            'label' => 'Building Name',
            'required' => false,
            'type' => 'text',
            'aliases' => ['building'],
            'help' => 'Required by the Office Branding category.',
            'samples' => ['', ''],
            'categories' => ['office'],
        ],
        [
            'key' => 'wall_length',
            'label' => 'Wall Length',
            'required' => false,
            'type' => 'text',
            'aliases' => ['walllength'],
            'help' => 'Office Branding only (the "Branding Type" field on the form).',
            'samples' => ['', ''],
            'categories' => ['office'],
        ],
        [
            'key' => 'area_auto',
            'label' => 'Total Area (Sq Ft)',
            'required' => false,
            'type' => 'decimal',
            'aliases' => ['totalarea', 'areasqft', 'areaauto'],
            'help' => 'Leave blank to calculate automatically as Width x Height.',
            'samples' => ['', ''],
            'scope' => 'full',
        ],
        [
            'key' => 'image_urls',
            'label' => 'Image URLs',
            'required' => false,
            'type' => 'list',
            'aliases' => ['images', 'imageurl', 'imagelinks', 'photos', 'photourls', 'imagefiles', 'imagefilenames'],
            'help' => 'Comma separated gallery photos. Write just the file names, e.g. '
                . 'site-front.jpg, site-side.jpg — put those exact files in a ZIP and upload it in the '
                . '"Images ZIP" box next to the sheet. The extension may be left off (site-front) as long '
                . 'as only one picture in the ZIP has that name. (A direct https:// link also works.) '
                . 'Do NOT paste a path from your own computer such as C:\\Users\\You\\Downloads\\site.jpg — '
                . 'the server cannot open your drive. JPG, PNG or WebP, up to '
                . MediaImageFetcher::MAX_GALLERY_IMAGES . ' per row and '
                . MediaImageFetcher::MAX_GALLERY_KB . 'KB each. Saved when the import is published.',
            'samples' => ['', ''],
        ],
        [
            'key' => 'panorama_url',
            'label' => 'Panorama Image URL',
            'required' => false,
            'type' => 'text',
            'aliases' => ['panorama', 'panoramaimage', 'panoramaurl', '360image', '360url', 'panoramafile'],
            'help' => 'One 360° panorama photo — the file name of a picture inside the images ZIP, e.g. '
                . 'site-360.jpg, or a direct https:// link. JPG, PNG or WebP, up to '
                . MediaImageFetcher::MAX_PANORAMA_KB . 'KB. Replaces the existing panorama on update.',
            'samples' => ['', ''],
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'required' => false,
            'type' => 'status',
            'aliases' => ['isactive', 'activestatus'],
            'help' => 'Active or Inactive. Blank is treated as Active.',
            'samples' => ['Active', 'Active'],
            'scope' => 'full',
        ],
    ];

    /**
     * Normalise any header / lookup value to a comparable key:
     * "Width (ft)" -> "widthft", " Front  Lit " -> "frontlit".
     */
    public static function normalise(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value)));
    }

    /**
     * The columns that apply to one category, in template order.
     *
     * Rules:
     *  - a column marked 'scope' => 'full' appears only in the all-categories
     *    template (auto / admin fields the Add Media form does not collect);
     *  - a column with no 'categories' key is a base field, kept for every
     *    category;
     *  - a column that lists categories is kept only when the slug contains one
     *    of its keywords.
     * A null / empty slug (the all-categories template) keeps everything.
     *
     * @return array<int,array>
     */
    public static function columnsForSlug(?string $slug): array
    {
        if ($slug === null || $slug === '') {
            return self::COLUMNS;
        }

        return array_values(array_filter(self::COLUMNS, function ($column) use ($slug) {
            if (($column['scope'] ?? null) === 'full') {
                return false;
            }

            if (empty($column['categories'])) {
                return true;
            }

            foreach ($column['categories'] as $keyword) {
                if (str_contains($slug, $keyword)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Header labels in template order. Pass a column subset to get the labels
     * for a single category template.
     *
     * @param array<int,array>|null $columns
     */
    public static function labels(?array $columns = null): array
    {
        return array_column($columns ?? self::COLUMNS, 'label');
    }

    /**
     * Map of every accepted normalised header => internal key.
     */
    public static function headerLookup(): array
    {
        $map = [];

        foreach (self::COLUMNS as $column) {
            $map[self::normalise($column['label'])] = $column['key'];
            $map[self::normalise($column['key'])] = $column['key'];

            foreach ($column['aliases'] as $alias) {
                $map[self::normalise($alias)] = $column['key'];
            }
        }

        return $map;
    }

    public static function requiredColumns(): array
    {
        return array_values(array_filter(self::COLUMNS, fn ($c) => $c['required']));
    }

    public static function optionalColumns(): array
    {
        return array_values(array_filter(self::COLUMNS, fn ($c) => !$c['required']));
    }

    public static function labelFor(string $key): string
    {
        foreach (self::COLUMNS as $column) {
            if ($column['key'] === $key) {
                return $column['label'];
            }
        }

        return $key;
    }

    /**
     * Flatten a keyed row into template column order, blanks included.
     *
     * @param array<string,mixed>   $row     keyed on the internal field name
     * @param array<int,array>|null $columns column subset (defaults to all)
     */
    public static function rowInColumnOrder(array $row, ?array $columns = null): array
    {
        return array_map(
            fn ($column) => (string) ($row[$column['key']] ?? ''),
            $columns ?? self::COLUMNS
        );
    }

    /**
     * Illustrative rows used only when the masters are still empty — a live
     * installation fills the template from its own data instead, see
     * MediaImportExportService::templateSampleRows().
     */
    public static function sampleRows(?array $columns = null): array
    {
        $columns = $columns ?? self::COLUMNS;
        $rows = [];

        for ($i = 0; $i < 2; $i++) {
            $rows[] = array_map(
                fn ($column) => $column['samples'][$i] ?? '',
                $columns
            );
        }

        return $rows;
    }
}
