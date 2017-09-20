<?php

class BlockTrailTest extends Orchestra\Testbench\TestCase
{
    private $client;
    private $passphrase;
    private $identifier;
    
    private $payIdentifier = 'AlexCarstensCattoriWallet1';
    private $payPassphrase = 'extreme-strong-password';
    
    public function setUp()
    {
        parent::setUp();
    }

    public function getPackageProviders($app)
    {
        return ['Bregananta\Blocktrail\BlocktrailServiceProvider'];
    }

    public function getPackageAliases($app)
    {
        return [
            'Blocktrail' => 'Bregananta\Blocktrail\BlocktrailFacade',
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('blocktrail.apiPrivateKey', getenv('BLOCKTRAIL_SECRET_API_KEY'));
        $app['config']->set('blocktrail.apiPublicKey', getenv('BLOCKTRAIL_PUBLIC_API_KEY'));
        $app['config']->set('blocktrail.network', getenv('BLOCKTRAIL_NETWORK'));
        $app['config']->set('blocktrail.testnet', getenv('BLOCKTRAIL_TESTNET'));
        $app['config']->set('blocktrail.version', getenv('BLOCKTRAIL_VERSION'));
    }
    
    private function randomString($length = 10)
    {
        $str = '';
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        return $str;
    }
    
    private function setPassphrase()
    {
        $this->passphrase = $this->randomString();
    }
    
    private function setIdentifier()
    {
        $this->identifier = $this->randomString();
    }
    
    public function testGetClient()
    {
        $res = blocktrail::getClient();
        $this->assertInstanceOf('Blocktrail\SDK\BlocktrailSDK', $res);
    }
    
    public function testWalletMethods()
    {
        $this->setIdentifier();
        $this->setPassphrase();
        
        $res = blocktrail::createWallet($this->identifier, $this->passphrase);
        $this->assertInstanceOf('Blocktrail\SDK\Wallet', $res[0]);
        
        $wallet = blocktrail::initWallet($this->payIdentifier, $this->payPassphrase);
        $this->assertInstanceOf('Blocktrail\SDK\Wallet', $wallet);
        
        $this->assertNull(blocktrail::lock($wallet));
        
        $address = blocktrail::getNewAddress($res[0]);
        
        $this->assertTrue(strlen($address) == 35);
        
        $this->assertNull(blocktrail::payAndLock($wallet, $this->payPassphrase, $address, 0.0001));
        
        $this->assertNull(blocktrail::unlock($wallet, $this->payPassphrase));
        
        $maxToSpend = blocktrail::getMaxSpendable($wallet);
        
        $this->assertArrayHasKey('max', (array) $maxToSpend);
        
        $isDeleted = blocktrail::easyDeleteWallet($this->identifier, $this->passphrase);
        
        $this->assertTrue($isDeleted);
        
        $identifier = blocktrail::getIdentifier($wallet);
        
        $this->assertNotNull($identifier);
        
        $this->assertArrayHasKey(
            'max', (array) blocktrail::walletMaxSpendable($identifier)
        );
        
        $this->assertTrue(count(blocktrail::getBalance($wallet)) == 2);
        
        $this->assertTrue(count(blocktrail::getNewAddressPair($wallet)) == 2);
    }
    
    public function testTransactionMethods()
    {
        $txBuilder = blocktrail::txBuilder();
        
        $this->assertInstanceOf('Blocktrail\SDK\TransactionBuilder', $txBuilder);
        
        $wallet = blocktrail::initWallet($this->payIdentifier, $this->payPassphrase);
        
        $txBuilder = blocktrail::getCoinSelection($wallet, $txBuilder);
        
        $this->assertInstanceOf('Blocktrail\SDK\TransactionBuilder', $txBuilder);
        
        $buildTx = blocktrail::buildTx($wallet, $txBuilder);
        
        $this->assertTrue(strlen($buildTx[0][0]['txid']) == 64);
        
        $optimalFeePerKB = blocktrail::getOptimalFeePerKB($wallet);
        $lowPriorityFeePerKB = blocktrail::getLowPriorityFeePerKB($wallet);

        $this->assertInternalType("int", $optimalFeePerKB);
        $this->assertInternalType("int", $lowPriorityFeePerKB);
        
        $paymentAddress = blocktrail::getNewAddress($wallet);
        
        $satoshiAmount = blocktrail::getSatoshiAmount(0.001);
        
        $txBuilder = blocktrail::addRecipients($txBuilder, $paymentAddress, $satoshiAmount);
        
        $this->assertInstanceOf('Blocktrail\SDK\TransactionBuilder', $txBuilder);
        
        $feeAndChange = blocktrail::determineFeeAndChange($wallet, $txBuilder, $lowPriorityFeePerKB, $optimalFeePerKB);
        
        $this->assertTrue(count($feeAndChange) == 2);
        
        $txBuilder = blocktrail::setFee($txBuilder, $feeAndChange[0]);
        
        $this->assertInstanceOf('Blocktrail\SDK\TransactionBuilder', $txBuilder);
    }
}


