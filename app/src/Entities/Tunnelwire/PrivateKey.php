<?php namespace Tunnelwire;

use Nymph\Nymph;
use Tilmeld\Tilmeld;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class PrivateKey extends \Nymph\Entity {
  const ETYPE = 'private_key';
  protected $clientEnabledMethods = [];
  public static $clientEnabledStaticMethods = ['current', 'upgradeEncryption'];
  protected $whitelistData = ['text', 'textOaep'];
  protected $protectedTags = [];
  protected $whitelistTags = [];

  public function __construct($id = 0) {
    $this->text = '';
    $this->textOaep = '';
    $this->acUser = \Tilmeld\Tilmeld::FULL_ACCESS;
    $this->acGroup = \Tilmeld\Tilmeld::NO_ACCESS;
    $this->acOther = \Tilmeld\Tilmeld::NO_ACCESS;
    parent::__construct($id);
  }

  public static function current() {
    if (!Tilmeld::gatekeeper()) {
      return false;
    }
    $key = Nymph::getEntity(
      ['class' => 'Tunnelwire\PrivateKey'],
      ['&',
        'ref' => ['user', Tilmeld::$currentUser]
      ]
    );
    if (!isset($key) || !$key->guid) {
      return false;
    }
    return $key;
  }

  public static function upgradeEncryption($textPrivateKey, $textPublicKey) {
    $privateKey = PrivateKey::current();
    $publicKey = PublicKey::current();

    if (!$privateKey || !$publicKey) {
      throw new \Exception('Key pair can\'t be found.');
    }

    if ($privateKey->textOaep ?? null && $publicKey->textOaep ?? null) {
      throw new \Exception('Key pair already contains OAEP keys.');
    }

    $privateKey->textOaep = $textPrivateKey;
    $publicKey->textOaep = $textPublicKey;

    if ($privateKey->save() && $publicKey->save()) {
      return [
        'private' => $privateKey,
        'public' => $publicKey
      ];
    } else {
      return false;
    }
  }

  public function save() {
    if (!Tilmeld::gatekeeper()) {
      // Only allow logged in users to save.
      return false;
    }
    try {
      v::notEmpty()
        ->attribute(
          'text',
          v::stringType()->notEmpty()->length(1, 2048),
          false
        )
        ->attribute(
          'textOaep',
          v::stringType()->notEmpty()->length(
            1,
            ceil(4096 * 1.4) + 54 // Base64 of 4KiB + RSA header/footer
          )
        )
        ->setName('private key')
        ->assert($this->getValidatable());
    } catch (NestedValidationException $exception) {
      throw new \Exception($exception->getFullMessage());
    }
    return parent::save();
  }
}
