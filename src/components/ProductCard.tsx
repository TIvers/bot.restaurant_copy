import React from 'react';
import { useParams } from 'react-router-dom';

const ProductCard: React.FC = () => {
  const { id } = useParams();

  return (
    <div>
      <h1>Карточка товара</h1>
      <p>Товар с ID: {id}</p>
      {/* Здесь будет отображаться информация о выбранном товаре */}
    </div>
  );
};

export default ProductCard;