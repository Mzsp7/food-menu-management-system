# 🚀 Deployment Guide - Food Menu Management System

This guide will help you deploy your Food Menu Management System to GitHub and Vercel.

## 📋 Prerequisites

- Git installed on your computer
- GitHub account
- Vercel account (free)

## 🔧 Step 1: Prepare Your Project

### 1.1 Initialize Git Repository

```bash
# Navigate to your project directory
cd "C:\Users\mzsp7\Music\Projects\food menu system"

# Initialize git repository
git init

# Add all files
git add .

# Make initial commit
git commit -m "Initial commit: Food Menu Management System"
```

### 1.2 Update Configuration Files

The following files have been created for deployment:

- ✅ `vercel.json` - Vercel deployment configuration
- ✅ `package.json` - Project metadata and dependencies
- ✅ `.gitignore` - Files to exclude from Git
- ✅ `DEPLOYMENT_GUIDE.md` - This guide

## 🐙 Step 2: Create GitHub Repository

### 2.1 Create Repository on GitHub

1. **Go to GitHub**: https://github.com
2. **Click "New repository"** (green button)
3. **Repository name**: `food-menu-management-system`
4. **Description**: `A comprehensive restaurant management system with admin and customer interfaces`
5. **Visibility**: Choose Public or Private
6. **Don't initialize** with README (we already have one)
7. **Click "Create repository"**

### 2.2 Connect Local Repository to GitHub

```bash
# Add GitHub remote (replace 'yourusername' with your GitHub username)
git remote add origin https://github.com/yourusername/food-menu-management-system.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## ⚡ Step 3: Deploy to Vercel

### 3.1 Deploy via Vercel Website

1. **Go to Vercel**: https://vercel.com
2. **Sign up/Login** with your GitHub account
3. **Click "New Project"**
4. **Import your repository**: `food-menu-management-system`
5. **Configure project**:
   - **Framework Preset**: Other
   - **Root Directory**: `./` (default)
   - **Build Command**: Leave empty
   - **Output Directory**: Leave empty
6. **Click "Deploy"**

### 3.2 Alternative: Deploy via Vercel CLI

```bash
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Deploy project
vercel

# Follow the prompts:
# ? Set up and deploy "food menu system"? [Y/n] y
# ? Which scope do you want to deploy to? [Your Account]
# ? Link to existing project? [y/N] n
# ? What's your project's name? food-menu-management-system
# ? In which directory is your code located? ./
```

## 🔧 Step 4: Configure Environment Variables (Optional)

For the full PHP functionality, you'll need to set up environment variables in Vercel:

### 4.1 In Vercel Dashboard

1. **Go to your project** in Vercel dashboard
2. **Click "Settings"** tab
3. **Click "Environment Variables"**
4. **Add the following variables**:

```env
DB_HOST=your-database-host
DB_NAME=food_menu_system
DB_USER=your-database-username
DB_PASS=your-database-password
JWT_SECRET=your-secret-key-here
```

### 4.2 Database Setup (For Full Functionality)

For the complete PHP/MySQL functionality, you'll need:

1. **Database hosting** (e.g., PlanetScale, Railway, or AWS RDS)
2. **Import your schema**: Upload `database/schema.sql`
3. **Configure connection**: Update environment variables

## 🌐 Step 5: Access Your Deployed Application

After deployment, your application will be available at:

- **Main URL**: `https://your-project-name.vercel.app`
- **Custom routes**:
  - Landing Page: `https://your-project-name.vercel.app/`
  - Admin Panel: `https://your-project-name.vercel.app/admin`
  - Customer Portal: `https://your-project-name.vercel.app/user`
  - Registration: `https://your-project-name.vercel.app/register`
  - Login: `https://your-project-name.vercel.app/login`

## 🔄 Step 6: Update and Redeploy

### 6.1 Making Changes

```bash
# Make your changes to the code
# Add and commit changes
git add .
git commit -m "Description of your changes"

# Push to GitHub
git push origin main
```

### 6.2 Automatic Deployment

Vercel automatically redeploys when you push to GitHub! 🎉

## 📱 Step 7: Test Your Deployment

### 7.1 Frontend Features (Work Immediately)

- ✅ **Landing page** with navigation
- ✅ **Customer portal** with menu browsing
- ✅ **Admin panel** interface
- ✅ **User registration** and login forms
- ✅ **Shopping cart** functionality
- ✅ **Responsive design** on mobile

### 7.2 Backend Features (Require Database)

- ⚠️ **User authentication** (needs database)
- ⚠️ **Data persistence** (needs database)
- ⚠️ **Image upload** (needs server)
- ⚠️ **Order processing** (needs database)

## 🎯 Step 8: Custom Domain (Optional)

### 8.1 Add Custom Domain in Vercel

1. **Go to project settings** in Vercel
2. **Click "Domains"** tab
3. **Add your domain**: `yourdomain.com`
4. **Configure DNS** as instructed by Vercel

## 🔧 Troubleshooting

### Common Issues

#### 1. **Build Errors**
- Check `vercel.json` configuration
- Ensure all files are committed to Git

#### 2. **PHP Not Working**
- Verify `vercel-php` runtime in `vercel.json`
- Check environment variables are set

#### 3. **Database Connection Issues**
- Verify database credentials
- Check database host accessibility
- Ensure database schema is imported

#### 4. **File Upload Issues**
- Check file permissions
- Verify upload directory exists
- Review file size limits

### Getting Help

- **Vercel Documentation**: https://vercel.com/docs
- **GitHub Issues**: Create issues in your repository
- **Vercel Support**: https://vercel.com/support

## 📊 Monitoring Your Application

### Vercel Analytics

1. **Enable Analytics** in Vercel dashboard
2. **Monitor performance** and usage
3. **Track user behavior** and errors

### GitHub Features

- **Issues tracking** for bug reports
- **Pull requests** for contributions
- **Actions** for CI/CD (optional)
- **Releases** for version management

## 🎉 Congratulations!

Your Food Menu Management System is now live on the internet! 

### What You've Accomplished:

- ✅ **GitHub Repository** - Version control and collaboration
- ✅ **Vercel Deployment** - Fast, global CDN hosting
- ✅ **Custom URLs** - Professional routing
- ✅ **Automatic Deployments** - Push to deploy
- ✅ **Professional Presentation** - Ready for portfolio/clients

### Next Steps:

1. **Share your project** - Add the URL to your portfolio
2. **Gather feedback** - Share with friends and potential users
3. **Add features** - Continue developing based on feedback
4. **Monitor usage** - Use Vercel analytics to track performance

Your restaurant management system is now ready for the world! 🌍
