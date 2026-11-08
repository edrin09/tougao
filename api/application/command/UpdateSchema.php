<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class UpdateSchema extends Command
{
    protected function configure()
    {
        $this->setName('update:schema')
            ->setDescription('更新数据库架构以适应新的需求');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始更新数据库架构...');

        // 执行SQL文件
        $sqlFile = PUBLIC_PATH . 'sql/update_tables_structure.sql';
        if (!file_exists($sqlFile)) {
            $output->writeln('<error>SQL文件不存在: ' . $sqlFile . '</error>');
            return;
        }

        try {
            $sql = file_get_contents($sqlFile);
            $statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');
            
            foreach ($statements as $statement) {
                try {
                    Db::execute($statement);
                    $output->writeln("<info>执行成功: " . substr($statement, 0, 50) . "...</info>");
                } catch (\Exception $e) {
                    $output->writeln("<error>执行失败: " . $statement . "</error>");
                    $output->writeln("<error>错误信息: " . $e->getMessage() . "</error>");
                }
            }
            
            $output->writeln('<info>数据库架构更新完成!</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>更新失败: ' . $e->getMessage() . '</error>');
        }
    }
}
