<?php

namespace Oro\Bundle\SaleBundle\Controller\Frontend;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Returns product offer by given quote product and quote demand via ajax.
 */
class AjaxQuoteProductController extends Controller
{
    /**
     * @Route(
     *      "/match-offer/{id}/{demandId}",
     *      name="oro_sale_quote_frontend_quote_product_match_offer",
     *      requirements={"id"="\d+", "demandId"="\d+"}
     * )
     * @ParamConverter("quoteDemand", class="OroSaleBundle:QuoteDemand", options={"id" = "demandId"})
     *
     * @param QuoteProduct $quoteProduct
     * @param QuoteDemand $quoteDemand
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function matchQuoteProductOfferAction(QuoteProduct $quoteProduct, QuoteDemand $quoteDemand, Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (!$authorizationChecker->isGranted('oro_sale_quote_demand_frontend_view', $quoteDemand) ||
            $quoteDemand->getQuote()->getId() !== $quoteProduct->getQuote()->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        $matcher = $this->get('oro_sale.service.quote_product_offer_matcher');
        $offer = $matcher->match($quoteProduct, $request->get('unit'), $request->get('qty'));

        return new JsonResponse($this->createResponseData($offer));
    }

    /**
     * @param QuoteProductOffer|null $offer
     * @return array
     */
    protected function createResponseData(QuoteProductOffer $offer = null)
    {
        if (!$offer) {
            return [];
        }

        $price = $offer->getPrice();

        if (!$price) {
            return [];
        }

        $formatter = $this->get('oro_locale.formatter.number');

        return [
            'id' => $offer->getId(),
            'unit' => $offer->getProductUnitCode(),
            'qty' => $offer->getQuantity(),
            'price' => $formatter->formatCurrency($price->getValue(), $price->getCurrency()),
        ];
    }
}
