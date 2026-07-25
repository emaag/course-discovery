<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Frontend;

/**
 * Renders one multi-select filter as a native `<details>`/`<summary>`
 * disclosure — collapsed by default, expands to a checkbox list on click
 * or Enter/Space on the summary. Used as-is for Providers and Categories
 * (the brief only requires "selection of multiple values" for those two),
 * and as the working, JS-optional base markup for Locations and Start
 * Dates, which `assets/js/combobox.js` progressively enhances into a real
 * `role="combobox"`/`role="listbox"` widget with arrow-key/Home/End/
 * typeahead navigation — see that file's docblock. The underlying
 * checkboxes rendered here are never removed by that enhancement, only
 * hidden, so this markup is what actually gets submitted either way, and
 * is a fully working (if plainer) "dropdown combobox" on its own with
 * JavaScript disabled.
 */
final class FilterFieldRenderer
{
    /**
     * @param list<array<string, mixed>> $options
     * @param list<int|string>           $selected
     */
    public static function renderCombobox(
        string $name,
        string $label,
        array $options,
        string $valueKey,
        string $labelKey,
        array $selected
    ): void {
        $fieldId = 'course-discovery-filter-' . $name;
        $selectedCount = count($selected);
        ?>
        <details class="course-discovery-filter" data-course-discovery-filter="<?php echo esc_attr($name); ?>">
            <summary id="<?php echo esc_attr($fieldId); ?>-summary">
                <?php echo esc_html($label); ?>
                <?php if ($selectedCount > 0) : ?>
                    <span class="course-discovery-filter__badge"><?php echo (int) $selectedCount; ?></span>
                <?php endif; ?>
            </summary>
            <div
                class="course-discovery-filter__panel"
                role="group"
                aria-labelledby="<?php echo esc_attr($fieldId); ?>-summary"
            >
                <?php if ($options === []) : ?>
                    <p class="course-discovery-filter__empty">
                        <?php esc_html_e('No options available.', 'course-discovery'); ?>
                    </p>
                <?php endif; ?>
                <?php foreach ($options as $option) :
                    $value = (string) $option[$valueKey];
                    $optionLabel = (string) $option[$labelKey];
                    $isChecked = in_array($option[$valueKey], $selected, true);
                    $inputId = $fieldId . '-' . sanitize_title($value);
                    ?>
                    <label class="course-discovery-filter__option" for="<?php echo esc_attr($inputId); ?>">
                        <input
                            type="checkbox"
                            id="<?php echo esc_attr($inputId); ?>"
                            name="<?php echo esc_attr($name); ?>[]"
                            value="<?php echo esc_attr($value); ?>"
                            <?php checked($isChecked); ?>
                        />
                        <?php echo esc_html($optionLabel); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </details>
        <?php
    }
}
