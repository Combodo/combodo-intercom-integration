<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Combodo\\iTop\\Extension\\IntercomIntegration\\' => 43,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Combodo\\iTop\\Extension\\IntercomIntegration\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Exception\\ModuleException' => __DIR__ . '/../..' . '/src/Exception/ModuleException.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Helper\\ConfigHelper' => __DIR__ . '/../..' . '/src/Helper/ConfigHelper.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Helper\\DatamodelObjectFinder' => __DIR__ . '/../..' . '/src/Helper/DatamodelObjectFinder.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Helper\\IconHelper' => __DIR__ . '/../..' . '/src/Helper/IconHelper.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Model\\Intercom\\Admin' => __DIR__ . '/../..' . '/src/Model/Intercom/Admin.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Model\\Intercom\\Contact' => __DIR__ . '/../..' . '/src/Model/Intercom/Contact.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Model\\Intercom\\Conversation' => __DIR__ . '/../..' . '/src/Model/Intercom/Conversation.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Model\\Intercom\\Webhook' => __DIR__ . '/../..' . '/src/Model/Intercom/Webhook.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Model\\Intercom\\WebhookForNewConversationMessage' => __DIR__ . '/../..' . '/src/Model/Intercom/WebhookForNewConversationMessage.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Inbound\\AbstractIncomingEventsHandler' => __DIR__ . '/../..' . '/src/Service/API/Inbound/AbstractIncomingEventsHandler.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Inbound\\CanvasKit\\AlertComponentsFactory' => __DIR__ . '/../..' . '/src/Service/API/Inbound/CanvasKit/AlertComponentsFactory.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Inbound\\CanvasKit\\ComponentFactory' => __DIR__ . '/../..' . '/src/Service/API/Inbound/CanvasKit/ComponentFactory.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Inbound\\CanvasKit\\InteractiveComponentSaveStates' => __DIR__ . '/../..' . '/src/Service/API/Inbound/CanvasKit/InteractiveComponentSaveStates.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Inbound\\IncomingCanvasKitsHandler' => __DIR__ . '/../..' . '/src/Service/API/Inbound/IncomingCanvasKitsHandler.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Inbound\\IncomingWebhooksHandler' => __DIR__ . '/../..' . '/src/Service/API/Inbound/IncomingWebhooksHandler.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Outbound\\ApiRequestSender' => __DIR__ . '/../..' . '/src/Service/API/Outbound/ApiRequestSender.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Service\\API\\Outbound\\ApiUrlGenerator' => __DIR__ . '/../..' . '/src/Service/API/Outbound/ApiUrlGenerator.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8::$classMap;

        }, null, ClassLoader::class);
    }
}
