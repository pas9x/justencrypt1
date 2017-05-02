<?php

use \app\Backup;

class Mod_backup extends WebuiModuleAdmin
{
    public function getName()
    {
        return 'backup';
    }

    public function getTitle()
    {
        return 'Резервные копии';
    }

    public function func_index()
    {
        $this->template = 'backup';
        $this->title = 'Резервные копии';
        $this->signs['actionCreate'] = $this->selfLink('createBackup');
        $this->signs['actionImport'] = $this->selfLink('importBackup');
        $this->display();
    }

    public function func_createBackup()
    {
        $fileName = Backup::createBackup(post('zipPassword', ''));
        $fileName = basename($fileName);
        displayOK('Резервная копия сохранена в файл ' . $fileName, $this->selfLink(), 'Вернуться к бэкапам');
    }

    public function func_importBackup()
    {
        if (empty($_FILES['backupFile']['tmp_name'])) {
            displayError('При сабмите формы вы не выбрали файл с бэкапом');
        }
        $fileName = $_FILES['backupFile']['tmp_name'];
        try {
            Backup::importBackup($fileName, $errors, post('zipPassword', ''));
        } catch (ErrorMessage $e) {
            displayError($e->getMessages());
        } catch (Exception $e) {
            errorLog('Unexpected error during backup import', $e);
            displayError('Импортировать бэкап не удалось из-за ошибки: ' . $e->getMessage());
        }
        if (empty($errors)) {
            displayOK('Импорт бэкапа завершён без ошибок', $this->selfLink(), 'Вернуться к бэкапам');
        }
        $this->template = 'report';
        $this->title = 'Отчёт по импорту бэкапа';
        $this->signs['errors'] = $errors;
        $this->display();
    }
}