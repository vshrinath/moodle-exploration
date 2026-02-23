<?php
$file = 'scripts/test/property_test_circular_dependency_prevention.php';
$content = file_get_contents($file);

$target = "        \$framework = \$DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);";
$replacement = "        \$framework = \$DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
        if (!\$framework) {
            \$framework = \$DB->get_record('competency_framework', [], 'id', IGNORE_MULTIPLE);
        }
        if (!\$framework) {
            throw new RuntimeException(\"No competency framework found. Run configure_competency_scales.php first.\");
        }";

$new_content = str_replace($target, $replacement, $content);
file_put_contents($file, $new_content);
echo "Updated " . substr_count($new_content, "if (!\$framework)") . " instances.";
