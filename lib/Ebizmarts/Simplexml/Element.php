<?php

class Ebizmarts_Simplexml_Element extends Varien_Simplexml_Element
{

    /**
     * Add CDATA text in a node
     *
     * @param string $cdataText The CDATA value  to add
     */
    private function addCData($cdataText)
    {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdataText));
    }

    /**
     * Create a child with CDATA value
     *
     * @param string $name The name of the child element to add.
     * @param string $cdataText The CDATA value of the child element.
     */
    public function addChildCData($name, $cdataText)
    {
        $child = $this->addChild($name);
        $child->addCData($cdataText);
    }

}