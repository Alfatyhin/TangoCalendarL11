<?php

namespace App\Services;

class StructuredQuery
{
    protected string $collection;
    protected array $filters = [];

    /**
     * Конструктор принимает имя коллекции.
     *
     * @param string $collection Имя коллекции Firestore.
     */
    public function __construct(string $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Добавляет фильтр для поля с оператором.
     *
     * Пример: status EQUAL "new"
     *
     * @param string $field Имя поля.
     * @param string $operator Оператор сравнения (EQUAL, LESS_THAN и т.д.).
     * @param mixed  $value Значение для сравнения.
     * @param string $valueType Тип значения (по умолчанию "stringValue").
     * @return $this
     */
    public function addFieldFilter(string $field, string $operator, $value, string $valueType = 'stringValue'): self
    {
        switch ($operator) {
            case'IN':
                $filter = $this->addInFilter($field, $value, $valueType);
                break;
            default:
                $filter = [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $field],
                        'op'    => $operator,
                        'value' => [
                            $valueType => $value,
                        ],
                    ],
                ];
        }



        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Добавляет фильтр IN для поля.
     *
     * Пример: type IN ["urgent", "regular"]
     *
     * @param string $field Имя поля.
     * @param array  $values Массив значений.
     * @param string $valueType Тип значения (по умолчанию "stringValue").
     * @return $this
     */
    private function addInFilter(string $field, array $values, string $valueType = 'stringValue'): self
    {
        $filterValues = array_map(function ($value) use ($valueType) {
            return [$valueType => $value];
        }, $values);

        $filter = [
            'fieldFilter' => [
                'field' => ['fieldPath' => $field],
                'op'    => 'IN',
                'value' => [
                    'arrayValue' => [
                        'values' => $filterValues,
                    ],
                ],
            ],
        ];

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Возвращает сформированный массив запроса для Firestore.
     *
     * Если добавлено несколько фильтров, они объединяются с помощью оператора AND.
     *
     * @return array Массив запроса structuredQuery.
     */
    public function getStructuredQuery(): array
    {
        $query = [
            'structuredQuery' => [
                'from' => [
                    ['collectionId' => $this->collection],
                ],
            ],
        ];

        if (count($this->filters) === 1) {
            $query['structuredQuery']['where'] = $this->filters[0];
        } elseif (count($this->filters) > 1) {
            $query['structuredQuery']['where'] = [
                'compositeFilter' => [
                    'op'      => 'AND',
                    'filters' => $this->filters,
                ],
            ];
        }

        return $query;
    }
}
