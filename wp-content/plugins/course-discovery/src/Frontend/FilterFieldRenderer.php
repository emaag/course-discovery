<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Frontend;

/**
 * Renders one multi-select filter as a native `<details>`/`<summary>`
 * disclosure — collapsed by default, expands to a checkbox listbox on
 * click or Enter/Space on the summary. Chosen over a hand-rolled ARIA
 * `role="combobox"` widget deliberately: `<details>` gets its open/close
 * keyboard behaviour from the browser for free, works with zero custom
 * JS (the form still submits and filters correctly with JS disabled),
 * and avoids the well-known ways a custom combobox's keyboard handling
 * can be subtly wrong. See Assumptions Made for why this satisfies the
 * brief's "dropdown combobox" requirement for Locations/Start Dates.
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
