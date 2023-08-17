<?php declare(strict_types = 1);
echo "<?php declare(strict_types = 1);\n"; ?>

namespace <?php echo $namespace; ?>;

interface <?php echo $class_name . "\n"; ?>
{
<?php
foreach ($constants as $key => $value) {
    echo '    public const ' . $key . " = '" . $value . "';\n";
}
?>
}
