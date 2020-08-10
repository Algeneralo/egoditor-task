<?php


use Symfony\Component\Console\Output\ConsoleOutput;

if (!function_exists("write_to_console")) {
    function write_to_console($message)
    {
        $output = new ConsoleOutput();
        $output->writeln("<info>{$message}</info>");
    }
}