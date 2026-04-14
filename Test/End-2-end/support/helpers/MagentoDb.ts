/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { execSync } from 'child_process';

/**
 * Database helper that wraps Docker exec + PDO boilerplate.
 * Eliminates repeated env.php → PDO connection setup across modules.
 */
export class MagentoDb {
  constructor(private container: string) {}

  /**
   * Run arbitrary PHP with $pdo pre-initialized as a PDO connection.
   * The PHP code should use $pdo and echo its result.
   */
  query(phpBody: string): string {
    const fullPhp = `
      foreach (['/var/www/html/app/etc/env.php', '/data/app/etc/env.php'] as $p) { if (file_exists($p)) { $env = include $p; break; } }
      $db = $env['db']['connection']['default'];
      $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);
      ${phpBody}
    `;

    const escaped = fullPhp.replace(/'/g, "'\\''");
    return execSync(`docker exec ${this.container} php -r '${escaped}'`, {
      stdio: 'pipe',
      timeout: 30000,
    }).toString();
  }

  /**
   * Set a config value in core_config_data (or delete if value is empty).
   */
  setConfig(path: string, value: string): void {
    if (value === '') {
      this.query(`
        $pdo->prepare("DELETE FROM core_config_data WHERE path = ? AND scope = 'default' AND scope_id = 0")->execute(['${path}']);
        echo 'deleted';
      `);
    } else {
      const b64Value = Buffer.from(value).toString('base64');
      this.query(`
        $value = base64_decode('${b64Value}');
        $stmt = $pdo->prepare("INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', 0, ?, ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute(['${path}', $value, $value]);
        echo 'ok';
      `);
    }
  }

  /**
   * Delete all config rows matching a LIKE pattern, reverting to config.xml defaults.
   */
  resetConfig(pathPattern: string): void {
    const b64Pattern = Buffer.from(pathPattern).toString('base64');
    this.query(`
      $pattern = base64_decode('${b64Pattern}');
      $deleted = $pdo->exec("DELETE FROM core_config_data WHERE path LIKE " . $pdo->quote($pattern));
      echo "reset:$deleted";
    `);
  }

  /**
   * Set a product EAV attribute value by SKU.
   * For select/dropdown attributes, pass the option label -- it will be resolved to the option ID.
   */
  setProductAttribute(sku: string, attributeCode: string, value: string): void {
    const b64Value = Buffer.from(value).toString('base64');
    this.query(`
      $sku = '${sku}';
      $attrCode = '${attributeCode}';
      $value = base64_decode('${b64Value}');

      $stmt = $pdo->prepare("SELECT entity_id FROM catalog_product_entity WHERE sku = ?");
      $stmt->execute([$sku]);
      $entityId = $stmt->fetchColumn();
      if (!$entityId) { echo "SKU not found: $sku"; exit; }

      $stmt = $pdo->prepare("SELECT attribute_id, backend_type, frontend_input FROM eav_attribute WHERE attribute_code = ? AND entity_type_id = 4");
      $stmt->execute([$attrCode]);
      $attr = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$attr) { echo "Attribute not found: $attrCode"; exit; }

      $attrId = $attr['attribute_id'];
      $backendType = $attr['backend_type'];
      $frontendInput = $attr['frontend_input'];

      if (in_array($frontendInput, ['select', 'multiselect'], true)) {
        $stmt = $pdo->prepare("SELECT eaov.option_id FROM eav_attribute_option eao JOIN eav_attribute_option_value eaov ON eao.option_id = eaov.option_id WHERE eao.attribute_id = ? AND eaov.value = ? AND eaov.store_id = 0 LIMIT 1");
        $stmt->execute([$attrId, $value]);
        $optionId = $stmt->fetchColumn();
        if (!$optionId) {
          $pdo->prepare("INSERT INTO eav_attribute_option (attribute_id, sort_order) VALUES (?, 0)")->execute([$attrId]);
          $optionId = $pdo->lastInsertId();
          $pdo->prepare("INSERT INTO eav_attribute_option_value (option_id, store_id, value) VALUES (?, 0, ?)")->execute([$optionId, $value]);
        }
        $value = $optionId;
      }

      $table = "catalog_product_entity_$backendType";
      $stmt = $pdo->prepare("INSERT INTO $table (attribute_id, store_id, entity_id, value) VALUES (?, 0, ?, ?) ON DUPLICATE KEY UPDATE value = ?");
      $stmt->execute([$attrId, $entityId, $value, $value]);
      echo "ok";
    `);
  }

  /**
   * Get a category entity_id by its name. Returns the ID string or null.
   */
  getCategoryIdByName(name: string): string | null {
    const b64Name = Buffer.from(name).toString('base64');
    const result = this.query(`
      $name = base64_decode('${b64Name}');
      $stmt = $pdo->prepare("SELECT e.entity_id FROM catalog_category_entity e JOIN catalog_category_entity_varchar v ON e.entity_id = v.entity_id JOIN eav_attribute a ON v.attribute_id = a.attribute_id WHERE a.attribute_code = 'name' AND a.entity_type_id = 3 AND v.value = ? AND v.store_id = 0 LIMIT 1");
      $stmt->execute([$name]);
      $id = $stmt->fetchColumn();
      echo $id ?: '';
    `).trim();

    return result || null;
  }
}
