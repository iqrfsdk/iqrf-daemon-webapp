#!/usr/bin/python3
# -*- coding: utf-8 -*-

"""
Copyright 2017 MICRORISC s.r.o.
Copyright 2017-2018 IQRF Tech s.r.o.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
"""

import argparse
import fileinput
import os
import subprocess

ARGS = argparse.ArgumentParser(description="IQRF Gateway Daemon webapp installer.")
ARGS.add_argument("-b", "--branch", action="store", dest="branch", default="stable", type=str, help="The used git branch.")
ARGS.add_argument("-d", "--dist", action="store", dest="dist", required=True, type=str, help="The used linux distribution.")
ARGS.add_argument("-v", "--ver", action="store", dest="ver", required=True, type=str, help="The version of used linux distribution.")
ARGS.add_argument("-s", "--stability", action="store", dest="stability", default="stable", type=str, help="The stability of the IQRF Gateway Daemon webapp.")

GIT_REPOSOTORY = "https://github.com/iqrfsdk/iqrf-daemon-webapp"
WEBSERVER_DIRECTORY = "/var/www/"
WEBAPP_DIRECTORY = WEBSERVER_DIRECTORY + "iqrf-daemon-webapp/"
SUDOERS_FILE = "/etc/sudoers"

def send_command(cmd):
	"""
	Execute shell command and return output
	@param cmd Command to exec
	@return string Output
	"""
	print("# " + cmd)
	output = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE).stdout.read()
	if output != b'':
		print(output.decode(encoding='UTF-8'))
	return output

def main():
	"""
	Main program function
	"""
	args = ARGS.parse_args()
	branch = args.branch
	dist = args.dist.lower()
	ver = args.ver.lower()
	stability = args.stability.lower()

	if dist == "debian" or dist == "raspbian":
		install_debian(ver, stability, branch)
	elif dist == "ubuntu":
		install_ubuntu(ver, stability, branch)

def install_debian(version, stability="stable", branch=None):
	"""
	Install IQRF Gateway Daemon webapp on Debian
	@param version Version of Debain
	@param stability Stability of the IQRF Gateway Daemon webapp
	@param branch Used git branch
	"""
	send_command("apt-get update")
	if version == "8" or version == "jessie" or version == "oldstable":
		# Install support for HTTPS repositories
		send_command("apt-get install apt-transport-https lsb-release ca-certificates")
		# Download PGP key for PHP 7.0 repository
		send_command("wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg")
		# Add PHP 7.0 repository
		send_command("sh -c 'echo \"deb https://packages.sury.org/php/ $(lsb_release -sc) main\" > /etc/apt/sources.list.d/php.list'")
		send_command("apt-get update")
		# Install sudo, nginx and php7.0
		send_command("apt-get install -y sudo php7.0 php7.0-common php7.0-fpm php7.0-curl php7.0-json php7.0-sqlite php7.0-mbstring php7.0-zip nginx-full zip unzip")
		# Install composer
		send_command("bash ./install-composer.sh")
		send_command("mv composer.phar /usr/bin/composer")
		chmod_dir()
		install_webapp(stability, branch)
		disable_default_nginx_virtualhost()
		create_nginx_virtualhost("iqrf-daemon-webapp_php7-0.localhost")
		fix_php_fpm_config("/etc/php/7.0/fpm/php.ini")
		enable_sudo()
		restart_service("sudo")
		restart_service("php7.0-fpm")
		restart_service("nginx")
	elif version == "9" or version == "stretch" or version == "stable":
		# Install sudo, nginx php7.0, composer and zip
		send_command("apt-get install -y sudo php7.0 php7.0-common php7.0-fpm php7.0-curl php7.0-json php7.0-sqlite php7.0-mbstring php7.0-zip composer nginx-full zip unzip")
		chmod_dir()
		install_webapp(stability, branch)
		disable_default_nginx_virtualhost()
		create_nginx_virtualhost("iqrf-daemon-webapp_php7-0.localhost")
		fix_php_fpm_config("/etc/php/7.0/fpm/php.ini")
		enable_sudo()
		restart_service("sudo")
		restart_service("php7.0-fpm")
		restart_service("nginx")

def install_ubuntu(version, stability="stable", branch=None):
	"""
	Install IQRF Gateway Daemon webapp on Ubuntu
	@param version Version of Ubuntu
	"""
	send_command("apt-get update")
	if version == "16.04" or version == "xenial" or version == "xerus":
		# Install sudo, nginx php7.0, composer and zip
		send_command("apt-get install -y sudo php7.0 php7.0-common php7.0-fpm php7.0-curl php7.0-json php7.0-sqlite php7.0-mbstring php7.0-zip composer nginx-full zip unzip")
		chmod_dir()
		install_webapp(stability, branch)
		disable_default_nginx_virtualhost()
		create_nginx_virtualhost("iqrf-daemon-webapp_php7-0.localhost")
		fix_php_fpm_config("/etc/php/7.0/fpm/php.ini")
		enable_sudo()
		restart_service("sudo")
		restart_service("php7.0-fpm")
		restart_service("nginx")

def install_webapp(stability, branch):
	"""
	Install IQRF Gateway Daemon webapp
	@param stability Stability of the IQRF Gateway Daemon webapp
	@param branch Git branch
	"""
	if stability == "dev":
		install_php_app(branch)
	elif stability == "stable" and (branch != "master" or branch != None):
		install_php_app(branch)

def install_php_app(branch=None):
	"""
	Install IQRF Gateway Daemon webapp
	@param branch Git branch
	"""
	if os.path.isdir(WEBAPP_DIRECTORY) and os.path.isdir(WEBAPP_DIRECTORY + "/.git"):
		send_command("rm -rf " + WEBAPP_DIRECTORY + "/temp/cache")
		send_command("cd " + WEBAPP_DIRECTORY + "; git pull origin")
		if branch != None:
			send_command("cd " + WEBAPP_DIRECTORY + "; git checkout " + branch)
		send_command("cd " + WEBAPP_DIRECTORY + "; git reset --hard")
		send_command("cd " + WEBAPP_DIRECTORY + "; composer update")
	elif os.path.isdir(WEBAPP_DIRECTORY) and not os.path.isdir(WEBAPP_DIRECTORY + "/.git"):
		send_command("rm -rf " + WEBAPP_DIRECTORY)
		send_command("cd " + WEBSERVER_DIRECTORY + "; git clone " + GIT_REPOSOTORY)
		if branch != None:
			send_command("cd " + WEBAPP_DIRECTORY + "; git checkout " + branch)
		send_command("cd " + WEBAPP_DIRECTORY + "; composer install")
	else:
		send_command("cd " + WEBSERVER_DIRECTORY + "; git clone " + GIT_REPOSOTORY)
		if branch != None:
			send_command("cd " + WEBAPP_DIRECTORY + "; git checkout " + branch)
		send_command("cd " + WEBAPP_DIRECTORY + "; composer install")
	send_command("chmod 777 " + WEBAPP_DIRECTORY + "/log/")
	send_command("chmod 777 " + WEBAPP_DIRECTORY + "/temp/")
	chown_dir(WEBAPP_DIRECTORY)


def chmod_dir(directory="/etc/iqrf-daemon"):
	"""
	Change mode of directory
	@param directory Directory
	"""
	send_command("chmod -R 666 " + directory)
	send_command("chmod 777 " + directory)

def chown_dir(directory, new_owner="www-data"):
	"""
	Change owner of directory
	@param directory Directory
	@param new_owner New owner of directory
	"""
	send_command("chown -R " + new_owner + ":" + new_owner + " " + directory)

def disable_default_nginx_virtualhost(nginx_dir="/etc/nginx"):
	"""
	Disable default nginx virtualhost
	@param nginx_dir Directory with configuration files for nginx
	"""
	virtualhost = nginx_dir + "/sites-enabled/default"
	if os.path.isfile(virtualhost):
		os.remove(virtualhost)

def create_nginx_virtualhost(virtualhost, nginx_dir="/etc/nginx"):
	"""
	Create nginx virtualhost
	@param virtualhost Path to file with virtualhost configuration
	@param nginx_dir Directory with configuration files for nginx
	"""
	virtualhost_name = "iqrf-daemon-webapp.localhost"
	available_virtualhost = nginx_dir + "/sites-available/" + virtualhost_name
	enabled_virtualhost = nginx_dir + "/sites-enabled/" + virtualhost_name
	send_command("cp -u nginx/" + virtualhost + " " + available_virtualhost)
	if not os.path.lexists(enabled_virtualhost):
		send_command("ln -s " + available_virtualhost + " " + enabled_virtualhost)

def fix_php_fpm_config(config_file):
	"""
	Fix PHP configuration
	@param config_file Path to PHP configuration file
	"""
	send_command("sed -i 's/;cgi\.fix_pathinfo=1/cgi\.fix_pathinfo=0/g' " + config_file)

def enable_sudo(user="www-data"):
	"""
	Enable sudo for specific user
	@param user User
	"""
	found = False
	sudoers = user + " ALL=(ALL) NOPASSWD:ALL"
	with fileinput.FileInput(SUDOERS_FILE) as file:
		for line in file:
			if line.strip() == sudoers:
				found = True
	if not found:
		send_command("echo \"" + sudoers + "\" >> " + SUDOERS_FILE)

def restart_service(name):
	"""
	Restart systemd service
	@param name Name of service to restart
	"""
	send_command("systemctl restart " + name + ".service")

if __name__ == "__main__":
	main()
