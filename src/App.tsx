import { BrowserRouter, Route, Routes, Navigate, useLocation } from 'react-router-dom';
import { useState } from 'react';
import Menu from './components/Menu';
import Category from './components/Category';
import ProductCard from './components/ProductCard';
import Cart from './components/Cart';
import Checkout from './components/Checkout';
import OrderHistory from './components/OrderHistory';
import Admin from './components/Admin';
import Header from './components/Header'; // Импортируем Header
import './index.css';

function App() {
  const [isAdmin, setIsAdmin] = useState(false);
  const location = useLocation();

  // Категории для хедера и меню
  const categories = [
    { name: 'Category 1', image: '../public/pizza.png' },
    { name: 'Category 2', image: '../public/burger.svg' },
    { name: 'Category 3', image: '../public/burger.png' },
    { name: 'Category 4', image: 'path/to/roll2.jpg' },
  ];

  // Продукты для меню
  const products = [
    { id: '1', category: 'Category 1', name: 'Ветчина и грибы', price: '250 ₽', description: 'Delicious pizza with ham and mushrooms', image: '../public/pizza.png' },
    { id: '2', category: 'Category 2', name: 'Бургер классический', price: '300 ₽', description: 'Classic burger with cheese', image: '../public/burger.png' },
    { id: '3', category: 'Category 3', name: 'Суши роллы', price: '500 ₽', description: 'Sushi rolls with fresh fish', image: 'path/to/roll2.jpg' },
    { id: '4', category: 'Category 1', name: 'Ветчина и грибы', price: '250 ₽', description: 'Delicious pizza with ham and mushrooms', image: '../public/pizza.png' },
    { id: '5', category: 'Category 2', name: 'Бургер классический', price: '300 ₽', description: 'Classic burger with cheese', image: '../public/burger.png' },
    { id: '6', category: 'Category 4', name: 'Суши роллы', price: '500 ₽', description: 'Sushi rolls with fresh fish', image: 'path/to/roll2.jpg' },
    { id: '7', category: 'Category 1', name: 'Ветчина и грибы', price: '250 ₽', description: 'Delicious pizza with ham and mushrooms', image: '../public/pizza.png' },
    { id: '8', category: 'Category 2', name: 'Бургер классический', price: '300 ₽', description: 'Classic burger with cheese', image: '../public/burger.png' },
    { id: '9', category: 'Category 4', name: 'Суши роллы', price: '500 ₽', description: 'Sushi rolls with fresh fish', image: 'path/to/roll2.jpg' },
    { id: '10', category: 'Category 1', name: 'Ветчина и грибы', price: '250 ₽', description: 'Delicious pizza with ham and mushrooms', image: '../public/pizza.png' },
    { id: '11', category: 'Category 4', name: 'Бургер классический', price: '300 ₽', description: 'Classic burger with cheese', image: '../public/burger.png' },
    { id: '12', category: 'Category 3', name: 'Суши роллы', price: '500 ₽', description: 'Sushi rolls with fresh fish', image: 'path/to/roll2.jpg' },
    { id: '13', category: 'Category 1', name: 'Ветчина и грибы', price: '250 ₽', description: 'Delicious pizza with ham and mushrooms', image: '../public/pizza.png' },
    { id: '14', category: 'Category 4', name: 'Бургер классический', price: '300 ₽', description: 'Classic burger with cheese', image: '../public/burger.png' },
    { id: '15', category: 'Category 3', name: 'Суши роллы', price: '500 ₽', description: 'Sushi rolls with fresh fish', image: 'path/to/roll2.jpg' },
    { id: '16', category: 'Category 1', name: 'Ветчина и грибы', price: '250 ₽', description: 'Delicious pizza with ham and mushrooms', image: '../public/pizza.png' },
    { id: '17', category: 'Category 2', name: 'Бургер классический', price: '300 ₽', description: 'Classic burger with cheese', image: '../public/burger.png' },
    { id: '18', category: 'Category 3', name: 'Суши роллы', price: '500 ₽', description: 'Sushi rolls with fresh fish', image: 'path/to/roll2.jpg' },
  ];

  // Логика отображения хедера в зависимости от маршрута
  const shouldShowHeader = !['/order-history', '/admin'].includes(location.pathname);
  const showCategories = location.pathname !== '/checkout';

  return (
    <>
      {shouldShowHeader && <Header categories={showCategories ? categories : []} />}
      <Routes>
        <Route path="/" element={<Navigate to="/menu" />} />
        {/* Передаем категории и продукты как пропсы в Menu */}
        <Route path="/menu" element={<Menu categories={categories} products={products} />} />
        <Route path="/category" element={<Category />} />
        <Route path="/product/:id" element={<ProductCard />} />
        <Route path="/cart" element={<Cart />} />
        <Route path="/checkout" element={<Checkout />} />
        <Route
          path="/order-history"
          element={isAdmin ? <OrderHistory /> : <Navigate to="/menu" />}
        />
        <Route
          path="/admin"
          element={isAdmin ? <Admin /> : <Navigate to="/menu" />}
        />
      </Routes>
    </>
  );
}

export default App;