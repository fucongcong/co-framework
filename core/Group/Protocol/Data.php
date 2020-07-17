<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: request.proto

namespace Group\Protocol;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>Group.Protocol.Data</code>
 */
class Data extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string val = 1;</code>
     */
    protected $val = '';
    /**
     * Generated from protobuf field <code>repeated string vals = 2;</code>
     */
    private $vals;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $val
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $vals
     * }
     */
    public function __construct($data = NULL) {
        \Group\Protocol\GPBMetadata\Request::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string val = 1;</code>
     * @return string
     */
    public function getVal()
    {
        return $this->val;
    }

    /**
     * Generated from protobuf field <code>string val = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setVal($var)
    {
        GPBUtil::checkString($var, True);
        $this->val = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated string vals = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getVals()
    {
        return $this->vals;
    }

    /**
     * Generated from protobuf field <code>repeated string vals = 2;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setVals($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->vals = $arr;

        return $this;
    }

}

