<?php

namespace AmazonMWSSPWrapper\AmazonSP;

use AmazonPHP\SellingPartner\Model\CatalogItem\Dimensions;
use AmazonPHP\SellingPartner\Model\CatalogItem\Item;

class CatalogItem
{

    public static function renderItem(Item $catalogInfo) {
        if(!empty($catalogInfo->getSalesRanks())) {
            $salesRanksResult = '            <SalesRankings>';
            foreach($catalogInfo->getSalesRanks() as $salesRanks) {
                foreach($salesRanks->getDisplayGroupRanks() as $displayGroupRank) {
                    $salesRanksResult.= '<SalesRank>
                    <ProductCategoryId>'.$displayGroupRank->getWebsiteDisplayGroup().'</ProductCategoryId>
                    <Rank>'.$displayGroupRank->getRank().'</Rank>
                </SalesRank>';
                }
                foreach($salesRanks->getClassificationRanks() as $classificationRank) {
                    $salesRanksResult.= '<SalesRank>
                    <ProductCategoryId>'.$classificationRank->getClassificationId().'</ProductCategoryId>
                    <Rank>'.$classificationRank->getRank().'</Rank>
                </SalesRank>';
                }
            }
            $salesRanksResult .= '</SalesRankings>';
        }
        $attributeResults = '            <AttributeSets>
                <ns2:ItemAttributes xml:lang="de-DE">';
        if(!empty($catalogInfo->getAttributes())) {
            $attributes = $catalogInfo->getAttributes();

            if(isset($attributes['brand'][0]->value)) {
                $attributeResults .= '<ns2:Brand>'.htmlentities($attributes['brand'][0]->value).'</ns2:Brand>'.PHP_EOL;
                // <ns2:Studio>Epson</ns2:Studio>
                // <ns2:Label>Epson</ns2:Label> ?
                // <ns2:Publisher>Epson</ns2:Publisher> ?
            }
            if(isset($attributes['color'][0]->value)) {
                $attributeResults .= '<ns2:Color>'.htmlentities($attributes['color'][0]->value).'</ns2:Color>'.PHP_EOL;
            }
            if(isset($attributes['item_name'][0]->value)) {
                $attributeResults .= '<ns2:Title>'.htmlentities($attributes['item_name'][0]->value).'</ns2:Title>'.PHP_EOL;
            }

            // <ns2:ProductTypeName>MONITOR</ns2:ProductTypeName> ?

            if(isset($attributes['manufacturer'][0]->value)) {
                $attributeResults .= '<ns2:Manufacturer>'.htmlentities($attributes['manufacturer'][0]->value).'</ns2:Manufacturer>'.PHP_EOL;
            }
            if(isset($attributes['model_name'][0]->value)) {
                $attributeResults .= '<ns2:Model>'.htmlentities($attributes['model_name'][0]->value).'</ns2:Model>'.PHP_EOL;
            }
            if(isset($attributes['item_package_quantity'][0]->value)) {
                $attributeResults .= '<ns2:PackageQuantity>'.htmlentities($attributes['item_package_quantity'][0]->value).'</ns2:PackageQuantity>'.PHP_EOL;
            }
            if(isset($attributes['part_number'][0]->value)) {
                $attributeResults .= '<ns2:PartNumber>'.htmlentities($attributes['part_number'][0]->value).'</ns2:PartNumber>'.PHP_EOL;
            }
        }
        /*  //TODO: images not yet implemented.
            $attributeResults.='<ns2:SmallImage>
                    <ns2:URL>https://m.media-amazon.com/images/I/41+uN23tm5L._SL75_.jpg</ns2:URL>
                    <ns2:Height Units="pixels">53</ns2:Height>
                    <ns2:Width Units="pixels">75</ns2:Width>
                </ns2:SmallImage>';
         */
        $summary = $catalogInfo->getSummaries()[0] ?? null;
        if($summary) {
            if($summary->getWebsiteDisplayGroupName()) {
                $attributeResults .= '<ns2:ProductGroup>'.htmlentities($summary->getWebsiteDisplayGroupName()).'</ns2:ProductGroup>'.PHP_EOL;
            }
        }

        if(!empty($catalogInfo->getDimensions())) {
            $itemDimensions = $catalogInfo->getDimensions()[0]->getItem();
            if(isset($itemDimensions)) {
                $attributeResults.='<ns2:ItemDimensions>'.PHP_EOL;
                $attributeResults.= self::addDimensions($itemDimensions);
                $attributeResults.='</ns2:ItemDimensions>'.PHP_EOL;
            }

            $packageDimensions = $catalogInfo->getDimensions()[0]->getPackage();
            if(isset($packageDimensions)) {
                $attributeResults.='<ns2:PackageDimensions>'.PHP_EOL;
                $attributeResults.= self::addDimensions($packageDimensions);
                $attributeResults.='</ns2:PackageDimensions>'.PHP_EOL;
            }

        }
        $attributeResults .= '</ns2:ItemAttributes>
            </AttributeSets>';

        return '<Product xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01"
            xmlns:ns2="http://mws.amazonservices.com/schema/Products/2011-10-01/default.xsd">
            <Identifiers>
                <MarketplaceASIN>
                    <MarketplaceId>'.$catalogInfo->getIdentifiers()[0]->getMarketplaceId().'</MarketplaceId>
                    <ASIN>'.$catalogInfo->getAsin().'</ASIN>
                </MarketplaceASIN>
            </Identifiers>
            '.($attributeResults).'
            '.($salesRanksResult ?? '').'
        </Product>';
    }

    private static function addDimensions(Dimensions $dimensions)
    {
        $attributeResults ='';
        if($dimensions->getWeight()) {
            $attributeResults.='<ns2:Weight Units="'.$dimensions->getWeight()->getUnit().'">'
                .$dimensions->getWeight()->getValue().'</ns2:Weight>'.PHP_EOL;
        }
        if($dimensions->getHeight()) {
            $attributeResults.='<ns2:Height Units="'.$dimensions->getHeight()->getUnit().'">'
                .$dimensions->getHeight()->getValue().'</ns2:Height>'.PHP_EOL;
        }
        if($dimensions->getLength()) {
            $attributeResults.='<ns2:Length Units="'.$dimensions->getLength()->getUnit().'">'
                .$dimensions->getLength()->getValue().'</ns2:Length>'.PHP_EOL;
        }
        if($dimensions->getWidth()) {
            $attributeResults.='<ns2:Width Units="'.$dimensions->getWidth()->getUnit().'">'
                .$dimensions->getWidth()->getValue().'</ns2:Width>'.PHP_EOL;
        }
        return $attributeResults;
    }
}