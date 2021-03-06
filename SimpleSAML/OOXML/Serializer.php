<?php

namespace SimpleSAML\OOXML;

global $start;
$start = microtime(TRUE);

class Serializer {

	protected $register;

	public function __construct($register = NULL) {
		if ($register === NULL) {
			$register = Register::getDefault();
		}
		$this->register = $register;
	}

	protected function addAttributes(SerializationContext $context, \DOMElement $target, Element $element) {
		foreach ($element->attributes as $name => $value) {
			if ($name[0] !== '{') {
				/* Not namespace prefixed attribute. */
				$target->setAttribute($name, $value);
				continue;
			}

			$nsEndPos = strpos($name, '}', 1);
			assert('$nsEndPos !== FALSE');
			$namespaceURI = substr($name, 1, $nsEndPos - 1);
			$localName = substr($name, $nsEndPos + 1);

			$qName = $context->qName($namespaceURI, $localName);
			$target->setAttributeNS($namespaceURI, $qName, $value);
		}
	}

	protected function processElement(SerializationContext $context, \DOMElement $parent = NULL, Element $element) {
		global $start;
		$xml = $context->createElement($element->namespaceURI, $element->localName);
		$this->addAttributes($context, $xml, $element);

		//for ($i = count($element->children) - 1; $i >= 0; $i--) {
		//$child = $element->children[$i];
		foreach ($element->children as $child) {
			if (microtime(TRUE) - $start > 30) {
				echo microtime(TRUE) - $start, "\n";
				break;
			}
			if (is_string($child)) {
				$context->addText($xml, $child);
			} elseif ($child instanceof Element) {
				$this->processElement($context, $xml, $child);
			} else {
				/* Unknown child type. */
				assert('FALSE');
			}
		}
		if ($parent !== NULL) {
			$parent->appendChild($xml);
		}
		return $xml;
	}

	public function serialize(Element $element) {
		$context = new SerializationContext($this->register);
		echo "Building DOM tree.\n";
		$root = $this->processElement($context, NULL, $element);
		echo "Saving XML as text...\n";
		$xml = $context->finish($root);
		echo "Done.\n";
		return $xml;
	}

}
