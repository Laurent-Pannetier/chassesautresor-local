<?php

namespace WPForms\Vendor\Box\Spout\Writer\Common\Creator;

use WPForms\Vendor\Box\Spout\Common\Creator\HelperFactory;
use WPForms\Vendor\Box\Spout\Common\Exception\UnsupportedTypeException;
use WPForms\Vendor\Box\Spout\Common\Helper\GlobalFunctionsHelper;
use WPForms\Vendor\Box\Spout\Common\Type;
use WPForms\Vendor\Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use WPForms\Vendor\Box\Spout\Writer\CSV\Manager\OptionsManager as CSVOptionsManager;
use WPForms\Vendor\Box\Spout\Writer\CSV\Writer as CSVWriter;
use WPForms\Vendor\Box\Spout\Writer\ODS\Creator\HelperFactory as ODSHelperFactory;
use WPForms\Vendor\Box\Spout\Writer\ODS\Creator\ManagerFactory as ODSManagerFactory;
use WPForms\Vendor\Box\Spout\Writer\ODS\Manager\OptionsManager as ODSOptionsManager;
use WPForms\Vendor\Box\Spout\Writer\ODS\Writer as ODSWriter;
use WPForms\Vendor\Box\Spout\Writer\WriterInterface;
use WPForms\Vendor\Box\Spout\Writer\XLSX\Creator\HelperFactory as XLSXHelperFactory;
use WPForms\Vendor\Box\Spout\Writer\XLSX\Creator\ManagerFactory as XLSXManagerFactory;
use WPForms\Vendor\Box\Spout\Writer\XLSX\Manager\OptionsManager as XLSXOptionsManager;
use WPForms\Vendor\Box\Spout\Writer\XLSX\Writer as XLSXWriter;
/**
 * Class WriterFactory
 * This factory is used to create writers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 */
class WriterFactory
{
    /**
     * This creates an instance of the appropriate writer, given the extension of the file to be written
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function createFromFile(string $path)
    {
        $extension = \strtolower(\pathinfo($path, \PATHINFO_EXTENSION));
        return self::createFromType($extension);
    }
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be written
     *
     * @param string $writerType Type of the writer to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function createFromType($writerType)
    {
        switch ($writerType) {
            case Type::CSV:
                return self::createCSVWriter();
            case Type::XLSX:
                return self::createXLSXWriter();
            case Type::ODS:
                return self::createODSWriter();
            default:
                throw new UnsupportedTypeException('No writers supporting the given type: ' . $writerType);
        }
    }
    /**
     * @return CSVWriter
     */
    private static function createCSVWriter()
    {
        $optionsManager = new CSVOptionsManager();
        $globalFunctionsHelper = new GlobalFunctionsHelper();
        $helperFactory = new HelperFactory();
        return new CSVWriter($optionsManager, $globalFunctionsHelper, $helperFactory);
    }
    /**
     * @return XLSXWriter
     */
    private static function createXLSXWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new XLSXOptionsManager($styleBuilder);
        $globalFunctionsHelper = new GlobalFunctionsHelper();
        $helperFactory = new XLSXHelperFactory();
        $managerFactory = new XLSXManagerFactory(new InternalEntityFactory(), $helperFactory);
        return new XLSXWriter($optionsManager, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }
    /**
     * @return ODSWriter
     */
    private static function createODSWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new ODSOptionsManager($styleBuilder);
        $globalFunctionsHelper = new GlobalFunctionsHelper();
        $helperFactory = new ODSHelperFactory();
        $managerFactory = new ODSManagerFactory(new InternalEntityFactory(), $helperFactory);
        return new ODSWriter($optionsManager, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }
}
