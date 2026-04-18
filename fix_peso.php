<?php

$file = 'app/Console/Commands/SyncFreteAnuncios.php';
$content = file_get_contents($file);

$old = "                                if (in_array(\$attrId, ['PACKAGE_WEIGHT', 'SELLER_PACKAGE_WEIGHT', 'WEIGHT'])) {
                                    \$dimArray['weight'] = \$numValue > 0 ? \$numValue / 1000 : 0;
                                }";

$new = "                                if (in_array(\$attrId, ['PACKAGE_WEIGHT', 'SELLER_PACKAGE_WEIGHT'])) {
                                    // Gramas - converter para kg
                                    \$dimArray['weight'] = \$numValue > 0 ? \$numValue / 1000 : 0;
                                } elseif (\$attrId === 'WEIGHT') {
                                    // WEIGHT pode ser kg ou g - verificar a unidade
                                    if (stripos(\$attrValue, 'kg') !== false) {
                                        \$dimArray['weight'] = \$numValue; // ja esta em kg
                                    } else {
                                        \$dimArray['weight'] = \$numValue > 0 ? \$numValue / 1000 : 0;
                                    }
                                }";

$newContent = str_replace($old, $new, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "Done\n";
} else {
    echo "Pattern not found\n";
}
