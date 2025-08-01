<?php

declare (strict_types=1);
namespace WPForms\Vendor\Square\Models;

use stdClass;
/**
 * Represents a
 * [BulkDeleteMerchantCustomAttributes]($e/MerchantCustomAttributes/BulkDeleteMerchantCustomAttributes)
 * request.
 */
class BulkDeleteMerchantCustomAttributesRequest implements \JsonSerializable
{
    /**
     * @var array<string,BulkDeleteMerchantCustomAttributesRequestMerchantCustomAttributeDeleteRequest>
     */
    private $values;
    /**
     * @param array<string,BulkDeleteMerchantCustomAttributesRequestMerchantCustomAttributeDeleteRequest> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }
    /**
     * Returns Values.
     * The data used to update the `CustomAttribute` objects.
     * The keys must be unique and are used to map to the corresponding response.
     *
     * @return array<string,BulkDeleteMerchantCustomAttributesRequestMerchantCustomAttributeDeleteRequest>
     */
    public function getValues() : array
    {
        return $this->values;
    }
    /**
     * Sets Values.
     * The data used to update the `CustomAttribute` objects.
     * The keys must be unique and are used to map to the corresponding response.
     *
     * @required
     * @maps values
     *
     * @param array<string,BulkDeleteMerchantCustomAttributesRequestMerchantCustomAttributeDeleteRequest> $values
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
