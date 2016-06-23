<?php namespace Octommerce\Octommerce\Controllers;

use Flash;
use Backend;
use Exception;
use BackendMenu;
use Backend\Classes\Controller;
use Octommerce\Octommerce\Models\OrderStatusLog;
use Octommerce\Octommerce\Models\Order;

/**
 * Orders Back-end Controller
 */
class Orders extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ImportExportController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $importExportConfig = 'config_import_export.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Octommerce.Octommerce', 'commerce', 'orders');
    }

    public function preview($recordId = null)
    {
        $this->vars['id'] = $recordId;
        $this->vars['order'] = Order::find($recordId);

        return $this->asExtension('FormController')->preview($recordId);
    }

    public function preview_onLoadChangeStatusForm($recordId = null)
    {
        try {
            $order = $this->formFindModelObject($recordId);
            $this->vars['currentStatus'] = isset($order->status->name) ? $order->status->name : '???';
            $this->vars['widget'] = $this->makeStatusFormWidget();
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('change_status_form');
    }

    public function preview_onChangeStatus($recordId = null)
    {
        $order = $this->formFindModelObject($recordId);
        $widget = $this->makeStatusFormWidget();
        $data = $widget->getSaveData();
        OrderStatusLog::createRecord($data['status'], $order, $data['note']);
        Flash::success('Order status updated successfully');
        return Backend::redirect(sprintf('octommerce/octommerce/orders/preview/%s', $order->id));
    }

    public function preview_onSendEmailToCustomer($recordId = null)
    {
        $order = $this->formFindModelObject($recordId);

        $order->sendEmailToCustomer();

        Flash::success('Email sent.');
    }

    protected function makeStatusFormWidget()
    {
        $config = $this->makeConfig('~/plugins/octommerce/octommerce/models/orderstatuslog/fields.yaml');
        $config->model = new OrderStatusLog;
        $config->arrayName = 'OrderStatusLog';
        $config->alias = 'statusLog';
        return $this->makeWidget('Backend\Widgets\Form', $config);
    }

    public function onDelete($id)
    {
        $order = Order::find($id);

        $order->delete();
    }
}