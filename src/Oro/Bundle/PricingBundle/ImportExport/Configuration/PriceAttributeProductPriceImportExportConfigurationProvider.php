<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Symfony\Component\Translation\TranslatorInterface;

class PriceAttributeProductPriceImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => PriceAttributeProductPrice::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_BUTTON_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.import.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.import_validation.button.label'),
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_BUTTON_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.export_template.button.label'),
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.export.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_POPUP_TITLE =>
                $this->translator->trans('oro.pricing.priceattributeproductprice.import.popup.title'),
        ]);
    }
}
