<?php

namespace Drupal\psu_form_error_handler;

use Drupal\Core\Form\FormElementHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\inline_form_errors\FormErrorHandler as InlineFormErrorHandler;

/**
 * Produces inline form errors with a custom sort algorithm.
 */
class FormErrorHandler extends InlineFormErrorHandler {

  /**
   * The theme manager service.
   */
  protected ?ThemeManagerInterface $themeManager;

  /**
   * Sets the admin context service.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The service to set.
   */
  public function setThemeManager(ThemeManagerInterface $theme_manager): void {
    $this->themeManager = $theme_manager;
  }

  /**
   * Determines if the current theme is compatible with this implementation.
   *
   * If the current theme, or any base theme is the 'ooe' theme, it is.
   *
   * @return bool
   *   TRUE if the current theme is compatible, otherwise FALSE.
   */
  protected function isCurrentThemeCompatible() {
    $is_compatible = FALSE;
    $theme = $this->themeManager->getActiveTheme();
    if ($theme === 'ooe') {
      $is_compatible = TRUE;
    }
    else {
      foreach ($theme->getBaseThemeExtensions() as $base_theme) {
        if ($base_theme->getName() === 'ooe') {
          $is_compatible = TRUE;
          break;
        }
      }
    }
    return $is_compatible;
  }

  /**
   * {@inheritdoc}
   */
  protected function displayErrorMessages(array $form, FormStateInterface $form_state): void {

    if (!$this->isCurrentThemeCompatible()) {
      parent::displayErrorMessages($form, $form_state);
      return;
    }

    $error_links = [];
    $errors = $form_state->getErrors();
    // Loop through all form errors and check if we need to display a link.
    foreach ($errors as $name => $error) {
      $form_element = FormElementHelper::getElementByName($name, $form);
      $title = FormElementHelper::getElementTitle($form_element);

      $is_visible_element = Element::isVisibleElement($form_element);
      $has_title = !empty($title);
      $has_id = !empty($form_element['#id']);

      if (!empty($form_element['#error_no_message'])) {
        unset($errors[$name]);
      }
      // If the element can be linked to, create a link to it.
      elseif ($is_visible_element && $has_title && $has_id) {
        $error_links[] = [
          '#wrapper_attributes' => [
            'class' => [
              'messages__item',
            ],
          ],
          '#type' => 'link',
          '#title' => [
            '#type' => 'inline_template',
            '#template' => '{% include "@psu-ooe/sprite/sprite.twig" with { name: "fa-circle-arrow-down"} only %}<span>{{ error }}</span>',
            '#context' => [
              'error' => $error,
            ],
          ],
          '#url' => Url::fromRoute('<none>', [], ['fragment' => $form_element['#id'], 'external' => TRUE]),
          '#attributes' => [
            'data-scroll-offset' => '20',
          ],
        ];
      }
      else {

        // Otherwise add it to the top of the list.
        array_unshift($error_links, [
          '#type' => 'inline_template',
          '#template' => '{% include "@psu-ooe/sprite/sprite.twig" with { name: "fa-exclamation-circle"} only %}<span>{{ error }}</span>',
          '#context' => [
            'error' => $error,
          ],
          '#wrapper_attributes' => [
            'class' => [
              'messages__item',
            ],
          ],
        ]);
      }
    }

    if (!empty($error_links)) {

      $summary = $this->formatPlural(count($error_links), 'Please resolve this issue before proceeding:', 'Please resolve these @count issues before proceeding:');

      $build = [
        '#type' => 'inline_template',
        '#template' => '<div class="messages__intro">{{ summary }}</div>{{ errors }}',
        '#context' => [
          'summary' => $summary,
          'errors' => [
            '#theme' => 'item_list',
            '#attributes' => [
              'class' => [
                'messages__list',
              ],
            ],
            '#items' => $error_links,
            '#list_type' => 'ol',
          ],
        ],
      ];
      $message = $this->renderer->renderPlain($build);
      $this->messenger->addMessage($message, 'form_error');
    }
  }

}
