<?php

declare (strict_types=1);
namespace WPForms\Vendor\Square\Models;

use stdClass;
/**
 * Represents a
 * [BulkUpsertCustomerCustomAttributes]($e/CustomerCustomAttributes/BulkUpsertCustomerCustomAttributes)
 * request.
 */
class BulkUpsertCustomerCustomAttributesRequest implements \JsonSerializable
{
    /**
     * @var array<string,BulkUpsertCustomerCustomAttributesRequestCustomerCustomAttributeUpsertRequest>
     */
    private $values;
    /**
     * @param array<string,BulkUpsertCustomerCustomAttributesRequestCustomerCustomAttributeUpsertRequest> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }
    /**
     * Returns Values.
     * A map containing 1 to 25 individual upsert requests. For each request, provide an
     * arbitrary ID that is unique for this `BulkUpsertCustomerCustomAttributes` request and the
     * information needed to create or update a custom attribute.
     *
     * @return array<string,BulkUpsertCustomerCustomAttributesRequestCustomerCustomAttributeUpsertRequest>
     */
    public function getValues() : array
    {
        return $this->values;
    }
    /**
     * Sets Values.
     * A map containing 1 to 25 individual upsert requests. For each request, provide an
     * arbitrary ID that is unique for this `BulkUpsertCustomerCustomAttributes` request and the
     * information needed to create or update a custom attribute.
     *
     * @required
     * @maps values
     *
     * @param array<string,BulkUpsertCustomerCustomAttributesRequestCustomerCustomAttributeUpsertRequest> $values
     */
    public function setValues(array $values) : void
    {
        $this->values = $values;
    }
    /**
     * Encode this object to JSON
     *
     * @param bool $asArrayWhenEmpty Whether to serialize this model as an array whenever no fields
     *        are set. (default: false)
     *
     * @return array|stdClass
     */
    #[\ReturnTypeWillChange] // @phan-suppress-current-line PhanUndeclaredClassAttribute for (php < 8.1)
    public function jsonSerialize(bool $asArrayWhenEmpty = \false)
    {
        $json = [];
        $json['values'] = $this->values;
        $json = \array_filter($json, function ($val) {
            return $val !== null;
        });
        return !$asArrayWhenEmpty && empty($json) ? new stdClass() : $json;
    }
}
